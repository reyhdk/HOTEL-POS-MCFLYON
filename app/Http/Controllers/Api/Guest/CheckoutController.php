<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Snap;
use Throwable;

class CheckoutController extends Controller
{
    /**
     * Mengambil semua rincian tagihan (folio) untuk sesi check-in yang aktif.
     */
    public function getFolio(Request $request)
    {
        $user = Auth::user();
        $activeCheckIn = $this->getActiveCheckIn($user);

        if (!$activeCheckIn) {
            return response()->json(null, 404);
        }

        // [PERBAIKAN] Standarkan pencarian status agar sama dengan Folio Admin
        $billableStatuses = ['pending', 'processing', 'delivering', 'completed'];

        $billableOrders = Order::where('user_id', $user->id)
                            ->whereIn('status', $billableStatuses) // Gunakan whereIn
                            ->with('items.menu')
                            ->get();

        $totalBill = $billableOrders->sum('total_price');

        return response()->json([
            'room' => $activeCheckIn->room,
            'booking' => $activeCheckIn->booking,
            'unpaid_orders' => $billableOrders, // Kirim data tagihan
            'total_unpaid' => (int) $totalBill, // Kirim total tagihan
        ]);
    }

    /**
     * Memproses pembayaran akhir saat checkout.
     */
    public function processCheckout(Request $request)
    {
        $user = Auth::user();
        $activeCheckIn = $this->getActiveCheckIn($user);

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Sesi check-in tidak ditemukan.'], 404);
        }

        // [PERBAIKAN] Hitung tagihan dari semua status yang relevan
        $billableStatuses = ['pending', 'processing', 'delivering', 'completed'];
        $totalBill = Order::where('user_id', $user->id)->whereIn('status', $billableStatuses)->sum('total_price');

        // Jika tidak ada tagihan sama sekali
        if ($totalBill <= 0) {
            DB::transaction(function () use ($activeCheckIn) {
                $activeCheckIn->update(['is_active' => false, 'check_out_time' => now()]);
                $activeCheckIn->room()->update(['status' => 'dirty']);
                if ($activeCheckIn->booking) {
                    $activeCheckIn->booking->update(['status' => 'completed']);
                }
            });
            return response()->json(['message' => 'Checkout berhasil! Tidak ada tagihan yang perlu dibayar.']);
        }

        // Jika ada tagihan, buat transaksi Midtrans
        try {
            $midtransOrderId = 'CHECKOUT-' . $activeCheckIn->booking_id . '-' . time();
            $params = [
                'transaction_details' => [ 'order_id' => $midtransOrderId, 'gross_amount' => (int) $totalBill ],
                'customer_details' => [ 'first_name' => $user->name, 'email' => $user->email, ],
            ];
            $snapToken = Snap::getSnapToken($params);
            if ($activeCheckIn->booking) {
                $activeCheckIn->booking->update(['midtrans_checkout_id' => $midtransOrderId]);
            }
            return response()->json(['snap_token' => $snapToken]);

        } catch (Throwable $e) {
            Log::error('Gagal memproses checkout tamu: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memulai proses checkout.'], 500);
        }
    }

    private function getActiveCheckIn($user)
    {
        return CheckIn::where('is_active', true)
            ->whereHas('booking', fn($q) => $q->where('user_id', $user->id))
            ->with(['guest', 'room', 'booking'])
            ->first();
    }
}