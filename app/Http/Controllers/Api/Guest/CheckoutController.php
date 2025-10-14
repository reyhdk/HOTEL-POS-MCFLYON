<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Order; // <-- Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Midtrans\Snap; // <-- Tambahkan ini
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
            return response()->json(['message' => 'Anda tidak memiliki sesi check-in yang aktif.'], 404);
        }

        // Ambil semua pesanan makanan yang statusnya 'unpaid'
        $unpaidOrders = Order::where('user_id', $user->id)
                             ->where('status', 'unpaid')
                             ->with('items.menu')
                             ->get();

        // Hitung total sisa tagihan dari pesanan makanan
        $totalUnpaid = $unpaidOrders->sum('total_price');

        return response()->json([
            'room' => $activeCheckIn->room,
            'booking' => $activeCheckIn->booking,
            'unpaid_orders' => $unpaidOrders,
            'total_unpaid' => (int) $totalUnpaid,
        ]);
    }

    /**
     * Memproses pembayaran akhir saat checkout dan menghasilkan snap_token jika ada tagihan.
     */
    public function processCheckout(Request $request)
    {
        $user = Auth::user();
        $activeCheckIn = $this->getActiveCheckIn($user);

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Sesi check-in tidak ditemukan.'], 404);
        }

        $totalUnpaid = Order::where('user_id', $user->id)->where('status', 'unpaid')->sum('total_price');

        // Jika tidak ada tagihan, langsung proses checkout
        if ($totalUnpaid <= 0) {
            DB::transaction(function () use ($activeCheckIn) {
                $activeCheckIn->update(['is_active' => false, 'check_out_time' => now()]);
                $activeCheckIn->room()->update(['status' => 'dirty']);
                $activeCheckIn->booking()->update(['status' => 'completed']);
            });
            return response()->json(['message' => 'Checkout berhasil! Tidak ada tagihan yang perlu dibayar.']);
        }

        try {
            $midtransOrderId = 'CHECKOUT-' . $activeCheckIn->booking_id . '-' . time();
            $params = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => (int) $totalUnpaid,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                ],
            ];

            $snapToken = Snap::getSnapToken($params);
            $activeCheckIn->booking->update(['midtrans_checkout_id' => $midtransOrderId]);
            return response()->json(['snap_token' => $snapToken]);
        } catch (Throwable $e) {
            Log::error('Gagal memproses checkout: ' . $e->getMessage());
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
