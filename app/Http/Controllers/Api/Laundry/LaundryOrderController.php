<?php

namespace App\Http\Controllers\Api\Laundry;

use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use App\Models\LaundryOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaundryOrderController extends Controller
{
    /**
     * Tampilkan semua request tamu (untuk halaman petugas)
     */
    public function index()
    {
        $orders = LaundryOrder::with(['user', 'staff', 'items.service'])
                    ->orderBy('created_at', 'desc')
                    ->get();
                    
        return response()->json(['success' => true, 'data' => $orders]);
    }

    /**
     * Petugas mengambil cucian ke kamar tamu (Pickup)
     * Disini petugas bisa langsung input berat/jumlah qty dari aplikasi
     */
    public function pickup(Request $request, $id)
    {
        $order = LaundryOrder::findOrFail($id);
        
        if ($order->status !== 'requested') {
            return response()->json(['success' => false, 'message' => 'Status tidak valid untuk pickup'], 400);
        }

        DB::beginTransaction();
        try {
            $order->status = 'picked_up';
            $order->staff_id = auth()->id(); // Set petugas yang menangani
            
            // Jika petugas menginput detail layanan saat pickup
            if ($request->has('items') && is_array($request->items)) {
                $totalPrice = 0;
                foreach ($request->items as $itemData) {
                    $item = LaundryOrderItem::create([
                        'laundry_order_id' => $order->id,
                        'laundry_service_id' => $itemData['service_id'],
                        'qty' => $itemData['qty'],
                        'subtotal' => $itemData['subtotal']
                    ]);
                    $totalPrice += $item->subtotal;
                }
                $order->total_price = $totalPrice;
            }
            
            $order->save();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Cucian berhasil dipickup', 'data' => $order]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal pickup: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mulai proses cuci -> OTOMATIS POTONG STOK GUDANG
     */
    public function process($id)
    {
        $order = LaundryOrder::with('items.service')->findOrFail($id);

        if ($order->status !== 'picked_up') {
            return response()->json(['success' => false, 'message' => 'Cucian belum dipickup atau sudah diproses'], 400);
        }

        DB::beginTransaction();
        try {
            $order->status = 'processing';
            $order->save();

            // Lakukan pemotongan stok gudang berdasarkan estimated_materials (JSON)
            foreach ($order->items as $item) {
                $service = $item->service;
                $materials = $service->estimated_materials; // Karena model dicast 'array', ini otomatis jadi array PHP

                if (is_array($materials)) {
                    foreach ($materials as $material) {
                        $warehouseItemId = $material['warehouse_item_id'];
                        // Qty material dikali qty cucian (misal: 50ml x 3 Kg = 150ml)
                        $qtyToDeduct = floatval($material['qty']) * floatval($item->qty);

                        // Ambil data gudang menggunakan DB query builder agar tidak trigger event
                        $warehouseItem = DB::table('warehouse_items')->where('id', $warehouseItemId)->first();

                        if ($warehouseItem && $warehouseItem->current_stock >= $qtyToDeduct) {
                            $newStock = $warehouseItem->current_stock - $qtyToDeduct;

                            // Update tabel stok
                            DB::table('warehouse_items')->where('id', $warehouseItemId)->update([
                                'current_stock' => $newStock,
                                'updated_at' => now()
                            ]);

                            // Catat Histori Transaksi Stok (Agar laporan rapi)
                            DB::table('stock_transactions')->insert([
                                'transaction_code' => 'LND/' . date('Ymd') . '/' . strtoupper(Str::random(5)),
                                'warehouse_item_id' => $warehouseItemId,
                                'transaction_type' => 'out',
                                'quantity' => $qtyToDeduct,
                                'unit_price' => $warehouseItem->cost_price,
                                'total_price' => $qtyToDeduct * $warehouseItem->cost_price,
                                'reference_type' => 'other',
                                'notes' => "Auto deduct: Laundry Order #{$order->order_number}",
                                'transaction_date' => now(),
                                'created_by' => auth()->id(),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Cucian sedang diproses. Stok gudang otomatis terpotong.', 'data' => $order]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memproses cucian: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Selesai & Kirim ke Kamar Tamu
     */
    public function deliver($id)
    {
        $order = LaundryOrder::findOrFail($id);
        
        if ($order->status !== 'processing') {
            return response()->json(['success' => false, 'message' => 'Cucian belum diproses'], 400);
        }

        $order->status = 'delivered';
        $order->save();

        // Disini nanti bisa ditambahkan trigger untuk menambah bill tagihan folio ke kamar

        return response()->json(['success' => true, 'message' => 'Cucian selesai dan siap dikirim ke kamar', 'data' => $order]);
    }
}