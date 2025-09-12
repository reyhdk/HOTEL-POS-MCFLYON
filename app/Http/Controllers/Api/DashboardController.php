<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// PERUBAHAN 1: Kita sekarang menggunakan model Order, bukan Payment
use App\Models\Order; 
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getStats(): JsonResponse
    {
        try {
            $today = Carbon::today();

            // PERUBAHAN 2: Query ke tabel 'orders' dengan status 'completed'
            $todaysRevenue = Order::where('status', 'completed')
                                    ->whereDate('created_at', $today)
                                    ->sum('total_price'); // PENTING: Sesuaikan 'total_price' jika perlu

            $todaysOrdersCount = Order::whereDate('created_at', $today)->count();
            $occupiedRoomsCount = Room::where('status', 'occupied')->count();
            $totalRooms = Room::count();

            return response()->json([
                'status' => true,
                'data' => [
                    'todays_revenue' => $todaysRevenue ?? 0,
                    'todays_orders_count' => $todaysOrdersCount ?? 0,
                    'occupied_rooms_count' => $occupiedRoomsCount ?? 0,
                    'total_rooms' => $totalRooms ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil statistik dasbor: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal mengambil data statistik.'], 500);
        }
    }

    public function getSalesChartData(): JsonResponse
    {
        try {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            // PERUBAHAN 3: Query ke tabel 'orders' untuk data grafik
            $sales = Order::query()
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total_price) as total') // PENTING: Sesuaikan 'total_price' jika perlu
                )
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get()
                ->keyBy('date');

            $labels = [];
            $seriesData = [];
            
            for ($i = 0; $i < 7; $i++) {
                $date = $startDate->copy()->addDays($i);
                $formattedDate = $date->format('Y-m-d');
                $labels[] = $date->format('d M');

                $saleOnDate = $sales->get($formattedDate);
                $seriesData[] = $saleOnDate ? $saleOnDate->total : 0;
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'labels' => $labels,
                    'series' => $seriesData,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data grafik penjualan: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal mengambil data grafik.'], 500);
        }
    }
}