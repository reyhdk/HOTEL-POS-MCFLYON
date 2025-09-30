<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App; // Import App facade
use Throwable;

class MidtransController extends Controller
{
    /**
     * Mengatur konfigurasi Midtrans saat controller diinisialisasi.
     */
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        // PERBAIKAN 1: Nonaktifkan verifikasi SSL hanya di lingkungan lokal (development)
        if (App::environment('local')) {
            Config::$curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
        }
    }

    /**
     * Membuat transaksi baru di Midtrans dan mengembalikan Snap Token.
     */
    public function createTransaction(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            // Ambil data order beserta relasi user dan item-itemnya
            // Asumsi: Model Order memiliki relasi 'items' yang berisi item-item pesanan
            $order = Order::with(['user', 'items.product'])->findOrFail($request->order_id);

            if (auth()->id() !== $order->user_id) {
                return response()->json(['message' => 'Tidak diizinkan.'], 403);
            }
            
            // PERBAIKAN 2: Tambahkan item_details untuk kelengkapan data
            $item_details = [];
            foreach ($order->items as $item) {
                $item_details[] = [
                    'id'       => $item->product->id,
                    'price'    => (int) $item->price,
                    'quantity' => (int) $item->quantity,
                    'name'     => $item->product->name,
                ];
            }

            $params = [
                'transaction_details' => [
                    'order_id'     => $order->id . '-' . time(),
                    'gross_amount' => (int) $order->total_price,
                ],
                'customer_details' => [
                    'first_name' => $order->user->name,
                    'email'      => $order->user->email,
                    'phone'      => $order->user->phone,
                ],
                'item_details' => $item_details, // Sertakan detail item
            ];

            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken]);

        } catch (Throwable $e) {
            Log::error('Midtrans Snap Token Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat transaksi pembayaran.'], 500);
        }
    }

    /**
     * Menerima notifikasi (webhook) dari Midtrans untuk update status pembayaran.
     */
    public function handleNotification(Request $request)
    {
        $payload = $request->all();
        $signatureKey = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('midtrans.server_key'));

        if ($payload['signature_key'] !== $signatureKey) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $orderId = explode('-', $payload['order_id'])[0];
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        // PERBAIKAN 3: Gunakan switch statement agar lebih rapi
        $transactionStatus = $payload['transaction_status'];
        $fraudStatus = $payload['fraud_status'] ?? 'accept';

        switch ($transactionStatus) {
            case 'capture':
            case 'settlement':
                if ($fraudStatus == 'accept') {
                    $order->status = 'paid';
                }
                break;
            case 'pending':
                $order->status = 'pending';
                break;
            case 'deny':
            case 'expire':
            case 'cancel':
                $order->status = 'cancelled';
                break;
        }

        $order->save();
        return response()->json(['message' => 'Notifikasi berhasil diproses.']);
    }
}