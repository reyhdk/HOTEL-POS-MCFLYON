<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// PERUBAHAN 1: Gunakan model Booking
use App\Models\Booking;
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

            // PERUBAHAN 2: Query ke tabel 'bookings' dengan status 'paid'
            $todaysRevenue = Booking::where('status', 'paid')
                                    ->whereDate('updated_at', $today) // Gunakan updated_at karena saat itu status berubah jadi 'paid'
                                    ->sum('total_price');

            $todaysBookingsCount = Booking::whereDate('created_at', $today)->count();
            $occupiedRoomsCount = Room::where('status', 'occupied')->count();
            $totalRooms = Room::count();

            return response()->json([
                'status' => true,
                'data' => [
                    'todays_revenue' => (int) $todaysRevenue,
                    'todays_orders_count' => $todaysBookingsCount, // Nama key tetap sama agar frontend tidak error
                    'occupied_rooms_count' => $occupiedRoomsCount,
                    'total_rooms' => $totalRooms,
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

            // PERUBAHAN 3: Query ke tabel 'bookings' untuk data grafik
            $sales = Booking::query()
                ->select(
                    DB::raw('DATE(updated_at) as date'), // Gunakan updated_at karena saat itu pembayaran terjadi
                    DB::raw('SUM(total_price) as total')
                )
                ->where('status', 'paid')
                ->whereBetween('updated_at', [$startDate, $endDate])
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
                $seriesData[] = $saleOnDate ? (int) $saleOnDate->total : 0;
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
