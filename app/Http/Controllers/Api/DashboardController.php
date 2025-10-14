<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Mengambil statistik utama (gabungan) untuk dashboard admin.
     */
    public function getStats(): JsonResponse
    {
        try {
            $today = Carbon::today();
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            // --- PENDAPATAN ---
            $todaysBookingRevenue = Booking::where('status', 'paid')->whereDate('updated_at', $today)->sum('total_price');
            $todaysOrderRevenue = Order::where('status', 'paid')->whereDate('updated_at', $today)->sum('total_price');
            $monthlyBookingRevenue = Booking::where('status', 'paid')->whereMonth('updated_at', $currentMonth)->whereYear('updated_at', $currentYear)->sum('total_price');
            $monthlyOrderRevenue = Order::where('status', 'paid')->whereMonth('updated_at', $currentMonth)->whereYear('updated_at', $currentYear)->sum('total_price');
            $yearlyBookingRevenue = Booking::where('status', 'paid')->whereYear('updated_at', $currentYear)->sum('total_price');
            $yearlyOrderRevenue = Order::where('status', 'paid')->whereYear('updated_at', $currentYear)->sum('total_price');

            // --- PESANAN ---
            $todaysBookingsCount = Booking::whereDate('created_at', $today)->count();
            $todaysFoodOrdersCount = Order::whereDate('created_at', $today)->count();
            $totalPaidFoodOrders = Order::where('status', 'paid')->count();
            $totalFoodItemsSold = OrderItem::whereHas('order', fn($q) => $q->where('status', 'paid'))->sum('quantity');

            // --- KAMAR ---
            $occupiedRoomsCount = Room::where('status', 'occupied')->count();
            $roomsNeedingCleaning = Room::whereIn('status', ['needs cleaning', 'request cleaning', 'dirty'])->count();
            $totalRooms = Room::count();

            return response()->json([
                'status' => true,
                'data' => [
                    // Pendapatan
                    'todays_revenue' => (int) ($todaysBookingRevenue + $todaysOrderRevenue),
                    'monthly_revenue' => (int) ($monthlyBookingRevenue + $monthlyOrderRevenue),
                    'yearly_revenue' => (int) ($yearlyBookingRevenue + $yearlyOrderRevenue),
                    // Pesanan
                    'todays_orders_count' => $todaysBookingsCount + $todaysFoodOrdersCount,
                    'total_food_orders' => $totalPaidFoodOrders,
                    'food_items_sold' => (int) $totalFoodItemsSold,
                    // Kamar
                    'occupied_rooms_count' => $occupiedRoomsCount,
                    'rooms_needing_cleaning' => $roomsNeedingCleaning,
                    'total_rooms' => $totalRooms,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil statistik dasbor: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal mengambil data statistik.'], 500);
        }
    }

    /**
     * Mengambil data grafik penjualan (gabungan) selama 7 hari terakhir.
     */
    public function getSalesChartData(): JsonResponse
    {
        try {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            $bookingSales = Booking::query()->select(DB::raw('DATE(updated_at) as date'), DB::raw('SUM(total_price) as total'))->where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate])->groupBy('date')->pluck('total', 'date');
            $orderSales = Order::query()->select(DB::raw('DATE(updated_at) as date'), DB::raw('SUM(total_price) as total'))->where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate])->groupBy('date')->pluck('total', 'date');

            $labels = [];
            $seriesData = [];

            for ($i = 0; $i < 7; $i++) {
                $date = $startDate->copy()->addDays($i);
                $formattedDate = $date->format('Y-m-d');
                $labels[] = $date->format('d M');

                $dailyBookingTotal = $bookingSales->get($formattedDate, 0);
                $dailyOrderTotal = $orderSales->get($formattedDate, 0);

                $seriesData[] = (int) ($dailyBookingTotal + $dailyOrderTotal);
            }

            return response()->json(['status' => true, 'data' => ['labels' => $labels, 'series' => $seriesData]]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data grafik penjualan: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal mengambil data grafik.'], 500);
        }
    }
}
