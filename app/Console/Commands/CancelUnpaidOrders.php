<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelUnpaidOrders extends Command
{
    /**
     * Nama dan signature dari perintah konsol.
     * Ini adalah nama yang akan kita panggil: 'orders:cancel-unpaid'
     */
    protected $signature = 'orders:cancel-unpaid';

    /**
     * Deskripsi dari perintah konsol.
     */
    protected $description = 'Mencari pesanan "pending" yang sudah lama dan membatalkannya, lalu mengembalikan stok.';

    /**
     * Logika utama dari perintah.
     */
    public function handle()
    {
        // Tentukan batas waktu, misalnya 15 menit yang lalu.
        $limit = now()->subMinutes(15);

        // Cari semua pesanan yang statusnya 'pending' dan dibuat sebelum batas waktu.
        $expiredOrders = Order::where('status', 'pending')
                              ->where('created_at', '<=', $limit)
                              ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Tidak ada pesanan kedaluwarsa yang ditemukan.');
            return 0; // Selesai
        }

        $this->info("Menemukan {$expiredOrders->count()} pesanan kedaluwarsa. Memproses...");

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order) {
                // 1. Kembalikan stok untuk setiap item di dalam pesanan
                foreach ($order->items as $item) {
                    // Kunci menu untuk keamanan
                    $menu = Menu::where('id', $item->menu_id)->lockForUpdate()->first();
                    if ($menu) {
                        $menu->increment('stock', $item->quantity);
                    }
                }

                // 2. Ubah status pesanan menjadi 'cancelled'
                $order->status = 'cancelled';
                $order->save();

                Log::info("Pesanan #{$order->id} telah dibatalkan secara otomatis karena melewati batas waktu pembayaran.");
            });
        }

        $this->info('Semua pesanan kedaluwarsa telah berhasil diproses.');
        return 0;
    }
}
