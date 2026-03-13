<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * GET /api/reports/hotel
     */
    public function hotelReport(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $year   = (int) $request->get('year',  now()->year);
        $month  = (int) $request->get('month', now()->month);
        $date   = $request->get('date',  now()->toDateString());

        $dates     = $this->getDateRange($period, $year, $month, $date);
        $startDate = $dates['start'];
        $endDate   = $dates['end'];

        $revenueStats = $this->getRevenueStats($startDate, $endDate);
        $expenseStats = $this->getExpenseStats($startDate, $endDate);

        $profit = $revenueStats['total_revenue'] - $expenseStats['total_expense'];
        $margin = $revenueStats['total_revenue'] > 0
            ? ($profit / $revenueStats['total_revenue']) * 100
            : 0;

        return response()->json([
            'data' => [
                'occupancy'    => $this->getOccupancyStats(),
                'revenue'      => $revenueStats,
                'expense'      => $expenseStats,
                'profit'       => [
                    'amount'            => $profit,
                    'margin_percentage' => round($margin, 2),
                    'status'            => $profit >= 0 ? 'untung' : 'rugi',
                ],
                'reservations' => $this->getReservationStats($startDate, $endDate),
                'guests'       => $this->getGuestStats($startDate, $endDate),
                'rooms'        => $this->getRoomStats($startDate, $endDate),
                'charts'       => $this->getChartData($year, $period, $month, $date),
            ],
            'meta' => [
                'period'     => $period,
                'start_date' => $startDate->toDateString(),
                'end_date'   => $endDate->toDateString(),
            ],
        ]);
    }

    /**
     * GET /api/reports/hotel/export  →  PDF Download
     */
    public function exportHotelReport(Request $request)
    {
        $period = $request->get('period', 'monthly');
        $year   = (int) $request->get('year',  now()->year);
        $month  = (int) $request->get('month', now()->month);
        $date   = $request->get('date',  now()->toDateString());

        $dates     = $this->getDateRange($period, $year, $month, $date);
        $startDate = $dates['start'];
        $endDate   = $dates['end'];

        $revenueStats = $this->getRevenueStats($startDate, $endDate);
        $expenseStats = $this->getExpenseStats($startDate, $endDate);

        $profit = $revenueStats['total_revenue'] - $expenseStats['total_expense'];
        $margin = $revenueStats['total_revenue'] > 0
            ? ($profit / $revenueStats['total_revenue']) * 100
            : 0;

        // ── Chart data (sudah difokuskan per periode) ──────────────
        $chartsData = $this->getChartData($year, $period, $month, $date);

        // ── Generate semua chart via QuickChart ────────────────────
        $charts = $this->generateAllCharts($chartsData, $period, $revenueStats, $year, $month);

        // ── Room chart (bar horizontal per tipe kamar) ─────────────
        $roomStats = $this->getRoomStats($startDate, $endDate);
        $chartRoomTypes = null;
        if (count($roomStats['popular_types']) > 0) {
            $chartRoomTypes = $this->fetchChart([
                'type' => 'horizontalBar',
                'data' => [
                    'labels'   => $roomStats['popular_types']->pluck('type')->toArray(),
                    'datasets' => [[
                        'label'           => 'Jumlah Booking',
                        'data'            => $roomStats['popular_types']->pluck('total_bookings')->toArray(),
                        'backgroundColor' => ['#009EF7','#50CD89','#F68B1E','#7239EA','#F1416C'],
                    ]],
                ],
                'options' => [
                    'title'  => ['display' => true, 'text' => 'Booking per Tipe Kamar'],
                    'legend' => ['display' => false],
                    'scales' => ['xAxes' => [['ticks' => ['beginAtZero' => true]]]],
                ],
            ], 600, 250);
        }

        $data = [
            'period'       => $period,
            'period_label' => $this->getPeriodLabel($period, $year, $month, $date),
            'start_date'   => $startDate->format('d M Y'),
            'end_date'     => $endDate->format('d M Y'),
            'occupancy'    => $this->getOccupancyStats(),
            'revenue'      => $revenueStats,
            'expense'      => $expenseStats,
            'profit'       => [
                'amount' => $profit,
                'margin' => round($margin, 2),
                'status' => $profit >= 0 ? 'Untung' : 'Rugi',
            ],
            'reservations'         => $this->getReservationStats($startDate, $endDate),
            'guests'               => $this->getGuestStats($startDate, $endDate),
            'rooms'                => $roomStats,
            // Chart images base64
            'chart_revenue_trend'  => $charts['revenue_trend'],
            'chart_pie_revenue'    => $charts['pie_revenue'],
            'chart_reservations'   => $charts['reservations'],
            'chart_occupancy_trend'=> $charts['occupancy_trend'],
            'chart_room_types'     => $chartRoomTypes,
        ];

        $pdf = Pdf::loadView('exports.laporan-hotel', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_Hotel_' . $period . '_' . now()->format('Ymd') . '.pdf');
    }

    // ════════════════════════════════════════════════════════════════
    // CHART GENERATOR
    // ════════════════════════════════════════════════════════════════

    /**
     * Generate semua chart, return array base64 image strings.
     */
    private function generateAllCharts(array $chartsData, string $period, array $revenueStats, int $year, int $month): array
    {
        $labels = $chartsData['labels'];

        // 1. Line chart: Tren Pendapatan (3 sumber)
        $revenueTrend = $this->fetchChart([
            'type' => 'line',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label'           => 'Total Pendapatan',
                        'data'            => $chartsData['total'],
                        'borderColor'     => '#F68B1E',
                        'backgroundColor' => 'rgba(246,139,30,0.08)',
                        'borderWidth'     => 2.5,
                        'fill'            => true,
                        'pointRadius'     => 3,
                    ],
                    [
                        'label'       => 'Kamar',
                        'data'        => $chartsData['revenue'],
                        'borderColor' => '#009EF7',
                        'borderWidth' => 2,
                        'fill'        => false,
                        'pointRadius' => 3,
                    ],
                    [
                        'label'       => 'Restoran',
                        'data'        => $chartsData['restaurant'],
                        'borderColor' => '#50CD89',
                        'borderWidth' => 2,
                        'fill'        => false,
                        'pointRadius' => 3,
                    ],
                    [
                        'label'       => 'Laundry',
                        'data'        => $chartsData['laundry'],
                        'borderColor' => '#7239EA',
                        'borderWidth' => 2,
                        'fill'        => false,
                        'pointRadius' => 3,
                    ],
                ],
            ],
            'options' => [
                'title'  => ['display' => true, 'text' => 'Tren Pendapatan — ' . $this->getPeriodLabel($period, $year, $month, '')],
                'legend' => ['position' => 'bottom'],
                'scales' => [
                    'yAxes' => [[
                        'ticks' => ['beginAtZero' => true, 'callback' => "function(v){return 'Rp'+v.toLocaleString('id-ID');}"],
                    ]],
                ],
            ],
        ], 700, 300);

        // 2. Pie chart: Komposisi pendapatan
        $pieRevenue = $this->fetchChart([
            'type' => 'pie',
            'data' => [
                'labels'   => ['Kamar', 'Restoran', 'Laundry'],
                'datasets' => [[
                    'data'            => [
                        $revenueStats['room_revenue'],
                        $revenueStats['restaurant_revenue'],
                        $revenueStats['laundry_revenue'],
                    ],
                    'backgroundColor' => ['#009EF7', '#50CD89', '#7239EA'],
                    'borderWidth'     => 1,
                ]],
            ],
            'options' => [
                'title'  => ['display' => true, 'text' => 'Komposisi Pendapatan'],
                'legend' => ['position' => 'bottom'],
            ],
        ], 380, 280);

        // 3. Bar chart: Volume Reservasi
        $reservationChart = $this->fetchChart([
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label'           => 'Reservasi',
                        'data'            => $chartsData['reservations'],
                        'backgroundColor' => 'rgba(114,57,234,0.7)',
                        'borderColor'     => '#7239EA',
                        'borderWidth'     => 1,
                    ],
                ],
            ],
            'options' => [
                'title'  => ['display' => true, 'text' => 'Volume Reservasi'],
                'legend' => ['display' => false],
                'scales' => ['yAxes' => [['ticks' => ['beginAtZero' => true]]]],
            ],
        ], 380, 280);

        // 4. Bar chart: Tren Okupansi (check-in tamu)
        $occupancyTrend = $this->fetchChart([
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'label'           => 'Check-in Tamu',
                        'data'            => $chartsData['occupancy'],
                        'backgroundColor' => 'rgba(80,205,137,0.7)',
                        'borderColor'     => '#50CD89',
                        'borderWidth'     => 1,
                        'type'            => 'bar',
                    ],
                    [
                        'label'       => 'Reservasi',
                        'data'        => $chartsData['reservations'],
                        'borderColor' => '#F68B1E',
                        'borderWidth' => 2,
                        'type'        => 'line',
                        'fill'        => false,
                        'pointRadius' => 3,
                    ],
                ],
            ],
            'options' => [
                'title'  => ['display' => true, 'text' => 'Tren Okupansi & Reservasi'],
                'legend' => ['position' => 'bottom'],
                'scales' => ['yAxes' => [['ticks' => ['beginAtZero' => true]]]],
            ],
        ], 700, 280);

        return [
            'revenue_trend'   => $revenueTrend,
            'pie_revenue'     => $pieRevenue,
            'reservations'    => $reservationChart,
            'occupancy_trend' => $occupancyTrend,
        ];
    }

    /**
     * Kirim request ke QuickChart API, return base64 data-URI atau null.
     */
    private function fetchChart(array $config, int $width = 700, int $height = 300): ?string
    {
        try {
            $response = Http::timeout(15)->post('https://quickchart.io/chart', [
                'chart'           => $config,
                'width'           => $width,
                'height'          => $height,
                'backgroundColor' => 'white',
                'format'          => 'png',
            ]);

            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
        } catch (\Exception $e) {
            Log::error('QuickChart error: ' . $e->getMessage());
        }
        return null;
    }

    // ════════════════════════════════════════════════════════════════
    // CHART DATA  —  label difokuskan sesuai periode
    // ════════════════════════════════════════════════════════════════

    private function getChartData(int $year, string $period, int $month, string $date): array
    {
        if ($period === 'daily') {
            return $this->getDailyChartData($year, $month);
        }

        if ($period === 'monthly') {
            return $this->getMonthlyFocusedChartData($year, $month);
        }

        // yearly — tampilkan semua 12 bulan
        return $this->getYearlyChartData($year);
    }

    /**
     * Period: MONTHLY  →  hanya tampil bulan sebelum, bulan ini, bulan sesudah.
     */
    private function getMonthlyFocusedChartData(int $year, int $month): array
    {
        $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        // Tentukan 3 bulan yang ditampilkan (prev, current, next)
        $months = [];
        $labels = [];
        for ($offset = -1; $offset <= 1; $offset++) {
            $m = $month + $offset;
            $y = $year;
            if ($m < 1)  { $m += 12; $y--; }
            if ($m > 12) { $m -= 12; $y++; }
            $months[] = ['year' => $y, 'month' => $m];
            $labels[] = $monthNames[$m] . ' ' . $y;
        }

        $roomSeries        = [];
        $restaurantSeries  = [];
        $laundrySeries     = [];
        $totalSeries       = [];
        $occupancySeries   = [];
        $reservationSeries = [];

        foreach ($months as $item) {
            $y = $item['year'];
            $m = $item['month'];

            $room = (float) DB::table('bookings')
                ->whereYear('created_at', $y)->whereMonth('created_at', $m)
                ->where('status', 'completed')->sum('total_price');

            $rest = (float) DB::table('orders')
                ->whereYear('created_at', $y)->whereMonth('created_at', $m)
                ->whereIn('status', ['paid', 'completed'])
                ->sum(DB::raw('CASE WHEN paid_amount > 0 THEN paid_amount ELSE total_price END'));

            $laundry = (float) DB::table('laundry_orders')
                ->whereYear('created_at', $y)->whereMonth('created_at', $m)
                ->where('status', 'delivered')->sum('total_price');

            $occ = (int) DB::table('check_ins')
                ->whereYear('check_in_time', $y)->whereMonth('check_in_time', $m)
                ->count();

            $rsv = (int) DB::table('bookings')
                ->whereYear('created_at', $y)->whereMonth('created_at', $m)
                ->count();

            $roomSeries[]        = $room;
            $restaurantSeries[]  = $rest;
            $laundrySeries[]     = $laundry;
            $totalSeries[]       = $room + $rest + $laundry;
            $occupancySeries[]   = $occ;
            $reservationSeries[] = $rsv;
        }

        return [
            'labels'       => $labels,
            'total'        => $totalSeries,
            'revenue'      => $roomSeries,
            'restaurant'   => $restaurantSeries,
            'laundry'      => $laundrySeries,
            'occupancy'    => $occupancySeries,
            'reservations' => $reservationSeries,
        ];
    }

    /**
     * Period: YEARLY  →  tampilkan semua 12 bulan.
     */
    private function getYearlyChartData(int $year): array
    {
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                   'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $roomByMonth = DB::table('bookings')
            ->whereYear('created_at', $year)->where('status', 'completed')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total_price) as total'))
            ->groupBy(DB::raw('MONTH(created_at)'))->pluck('total', 'month');

        $restaurantByMonth = DB::table('orders')
            ->whereYear('created_at', $year)->whereIn('status', ['paid', 'completed'])
            ->select(DB::raw('MONTH(created_at) as month'),
                     DB::raw('SUM(CASE WHEN paid_amount > 0 THEN paid_amount ELSE total_price END) as total'))
            ->groupBy(DB::raw('MONTH(created_at)'))->pluck('total', 'month');

        $laundryByMonth = DB::table('laundry_orders')
            ->whereYear('created_at', $year)->where('status', 'delivered')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total_price) as total'))
            ->groupBy(DB::raw('MONTH(created_at)'))->pluck('total', 'month');

        $occupancyByMonth = DB::table('check_ins')
            ->whereYear('check_in_time', $year)
            ->select(DB::raw('MONTH(check_in_time) as month'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('MONTH(check_in_time)'))->pluck('total', 'month');

        $reservationByMonth = DB::table('bookings')
            ->whereYear('created_at', $year)
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('MONTH(created_at)'))->pluck('total', 'month');

        $roomSeries = $restaurantSeries = $laundrySeries = [];
        $totalSeries = $occupancySeries = $reservationSeries = [];

        for ($m = 1; $m <= 12; $m++) {
            $room = (float) ($roomByMonth[$m] ?? 0);
            $rest = (float) ($restaurantByMonth[$m] ?? 0);
            $lnd  = (float) ($laundryByMonth[$m] ?? 0);
            $roomSeries[]        = $room;
            $restaurantSeries[]  = $rest;
            $laundrySeries[]     = $lnd;
            $totalSeries[]       = $room + $rest + $lnd;
            $occupancySeries[]   = (int) ($occupancyByMonth[$m] ?? 0);
            $reservationSeries[] = (int) ($reservationByMonth[$m] ?? 0);
        }

        return [
            'labels'       => $labels,
            'total'        => $totalSeries,
            'revenue'      => $roomSeries,
            'restaurant'   => $restaurantSeries,
            'laundry'      => $laundrySeries,
            'occupancy'    => $occupancySeries,
            'reservations' => $reservationSeries,
        ];
    }

    /**
     * Period: DAILY  →  tampilkan per tanggal dalam bulan yang dipilih.
     */
    private function getDailyChartData(int $year, int $month): array
    {
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $labels      = array_map(fn($d) => (string) $d, range(1, $daysInMonth));

        $roomByDay = DB::table('bookings')
            ->whereYear('created_at', $year)->whereMonth('created_at', $month)
            ->where('status', 'completed')
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('SUM(total_price) as total'))
            ->groupBy(DB::raw('DAY(created_at)'))->pluck('total', 'day');

        $restaurantByDay = DB::table('orders')
            ->whereYear('created_at', $year)->whereMonth('created_at', $month)
            ->whereIn('status', ['paid', 'completed'])
            ->select(DB::raw('DAY(created_at) as day'),
                     DB::raw('SUM(CASE WHEN paid_amount > 0 THEN paid_amount ELSE total_price END) as total'))
            ->groupBy(DB::raw('DAY(created_at)'))->pluck('total', 'day');

        $laundryByDay = DB::table('laundry_orders')
            ->whereYear('created_at', $year)->whereMonth('created_at', $month)
            ->where('status', 'delivered')
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('SUM(total_price) as total'))
            ->groupBy(DB::raw('DAY(created_at)'))->pluck('total', 'day');

        $occupancyByDay = DB::table('check_ins')
            ->whereYear('check_in_time', $year)->whereMonth('check_in_time', $month)
            ->select(DB::raw('DAY(check_in_time) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('DAY(check_in_time)'))->pluck('total', 'day');

        $reservationByDay = DB::table('bookings')
            ->whereYear('created_at', $year)->whereMonth('created_at', $month)
            ->select(DB::raw('DAY(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('DAY(created_at)'))->pluck('total', 'day');

        $roomSeries = $restaurantSeries = $laundrySeries = [];
        $totalSeries = $occupancySeries = $reservationSeries = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $room = (float) ($roomByDay[$d] ?? 0);
            $rest = (float) ($restaurantByDay[$d] ?? 0);
            $lnd  = (float) ($laundryByDay[$d] ?? 0);
            $roomSeries[]        = $room;
            $restaurantSeries[]  = $rest;
            $laundrySeries[]     = $lnd;
            $totalSeries[]       = $room + $rest + $lnd;
            $occupancySeries[]   = (int) ($occupancyByDay[$d] ?? 0);
            $reservationSeries[] = (int) ($reservationByDay[$d] ?? 0);
        }

        return [
            'labels'       => $labels,
            'total'        => $totalSeries,
            'revenue'      => $roomSeries,
            'restaurant'   => $restaurantSeries,
            'laundry'      => $laundrySeries,
            'occupancy'    => $occupancySeries,
            'reservations' => $reservationSeries,
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ════════════════════════════════════════════════════════════════

    private function getDateRange($period, $year, $month, $date)
    {
        if ($period === 'daily') {
            $start = Carbon::parse($date)->startOfDay();
            $end   = Carbon::parse($date)->endOfDay();
        } elseif ($period === 'yearly') {
            $start = Carbon::create($year)->startOfYear();
            $end   = Carbon::create($year)->endOfYear();
        } else {
            $start = Carbon::create($year, $month)->startOfMonth();
            $end   = Carbon::create($year, $month)->endOfMonth();
        }
        return ['start' => $start, 'end' => $end];
    }

    private function getPeriodLabel($period, $year, $month, $date): string
    {
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        if ($period === 'daily')  return Carbon::parse($date)->format('d F Y');
        if ($period === 'yearly') return 'Tahun ' . $year;
        return 'Bulan ' . ($months[$month] ?? '') . ' ' . $year;
    }

    // ── Revenue ──────────────────────────────────────────────────────

    private function getRevenueStats(Carbon $start, Carbon $end): array
    {
        $roomRevenue = DB::table('bookings')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('total_price');

        $roomBookings = DB::table('bookings')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->count();

        $restaurantRevenue = DB::table('orders')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'completed'])
            ->sum('paid_amount');

        $restaurantRevenueFallback = DB::table('orders')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'completed'])
            ->where('paid_amount', 0)
            ->sum('total_price');

        $restaurantRevenue += $restaurantRevenueFallback;

        $restaurantOrders = DB::table('orders')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'completed'])
            ->count();

        $laundryRevenue = DB::table('laundry_orders')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'delivered')
            ->sum('total_price');

        $laundryOrders = DB::table('laundry_orders')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'delivered')
            ->count();

        $totalRevenue = (float) $roomRevenue + (float) $restaurantRevenue + (float) $laundryRevenue;

        return [
            'room_revenue'       => (float) $roomRevenue,
            'room_bookings'      => (int)   $roomBookings,
            'restaurant_revenue' => (float) $restaurantRevenue,
            'restaurant_orders'  => (int)   $restaurantOrders,
            'laundry_revenue'    => (float) $laundryRevenue,
            'laundry_orders'     => (int)   $laundryOrders,
            'total_revenue'      => $totalRevenue,
        ];
    }

    // ── Expense ──────────────────────────────────────────────────────

    private function getExpenseStats(Carbon $start, Carbon $end): array
    {
        $expense = DB::table('cash_flows')
            ->whereBetween('created_at', [$start, $end])
            ->where('type', 'expense')
            ->sum('amount');

        return ['total_expense' => (float) $expense];
    }

    // ── Occupancy ─────────────────────────────────────────────────────

    private function getOccupancyStats(): array
    {
        $totalRooms = DB::table('rooms')->where('status', '!=', 'maintenance')->count();
        $occupied   = DB::table('rooms')->where('status', 'occupied')->count();
        $maintenance= DB::table('rooms')->where('status', 'maintenance')->count();
        $dirty      = DB::table('rooms')->whereIn('status', ['dirty', 'request cleaning', 'needs cleaning'])->count();
        $rate       = $totalRooms > 0 ? round(($occupied / $totalRooms) * 100, 1) : 0;

        return [
            'total_rooms'       => $totalRooms,
            'occupied_rooms'    => $occupied,
            'available_rooms'   => max(0, $totalRooms - $occupied - $dirty),
            'maintenance_rooms' => $maintenance,
            'dirty_rooms'       => $dirty,
            'occupancy_rate'    => $rate,
        ];
    }

    // ── Reservations ─────────────────────────────────────────────────

    private function getReservationStats(Carbon $start, Carbon $end): array
    {
        $total     = DB::table('bookings')->whereBetween('created_at', [$start, $end])->count();
        $online    = DB::table('bookings')->whereBetween('created_at', [$start, $end])->where('midtrans_order_id', 'LIKE', 'BOOK-%')->count();
        $cancelled = DB::table('bookings')->whereBetween('created_at', [$start, $end])->where('status', 'cancelled')->count();
        $confirmed = DB::table('bookings')->whereBetween('created_at', [$start, $end])->where('status', 'confirmed')->count();
        $completed = DB::table('bookings')->whereBetween('created_at', [$start, $end])->where('status', 'completed')->count();
        $checkedIn = DB::table('bookings')->whereBetween('created_at', [$start, $end])->where('status', 'checked_in')->count();

        return [
            'total'             => $total,
            'online'            => $online,
            'offline'           => $total - $online,
            'cancelled'         => $cancelled,
            'confirmed'         => $confirmed,
            'checked_in'        => $checkedIn,
            'completed'         => $completed,
            'today'             => DB::table('bookings')->whereDate('created_at', now()->toDateString())->count(),
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0,
        ];
    }

    // ── Guests ───────────────────────────────────────────────────────

    private function getGuestStats(Carbon $start, Carbon $end): array
    {
        $totalGuests   = DB::table('check_ins')->whereBetween('check_in_time', [$start, $end])->count();
        $checkInToday  = DB::table('check_ins')->whereDate('check_in_time', now()->toDateString())->count();
        $checkOutToday = DB::table('check_ins')->whereDate('check_out_time', now()->toDateString())->where('is_active', 0)->count();
        $currentGuests = DB::table('check_ins')->where('is_active', 1)->count();
        $avgStay       = DB::table('bookings')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['completed', 'checked_in'])
            ->whereNotNull('check_out_date')
            ->selectRaw('AVG(DATEDIFF(check_out_date, check_in_date)) as avg_days')
            ->value('avg_days');

        return [
            'total_period'      => $totalGuests,
            'currently_staying' => $currentGuests,
            'check_in_today'    => $checkInToday,
            'check_out_today'   => $checkOutToday,
            'avg_stay_days'     => $avgStay ? round((float) $avgStay, 1) : 0,
        ];
    }

    // ── Rooms ─────────────────────────────────────────────────────────

    private function getRoomStats(Carbon $start, Carbon $end): array
    {
        $popularTypes = DB::table('bookings')
            ->join('rooms', 'bookings.room_id', '=', 'rooms.id')
            ->whereBetween('bookings.created_at', [$start, $end])
            ->whereIn('bookings.status', ['confirmed', 'checked_in', 'completed'])
            ->select('rooms.type', DB::raw('COUNT(*) as total_bookings'))
            ->groupBy('rooms.type')
            ->orderByDesc('total_bookings')
            ->limit(5)->get();

        $maintenanceRooms = DB::table('rooms')
            ->where('status', 'maintenance')
            ->select('room_number', 'type')->get();

        $mostUsedRooms = DB::table('check_ins')
            ->join('rooms', 'check_ins.room_id', '=', 'rooms.id')
            ->whereBetween('check_ins.check_in_time', [$start, $end])
            ->select('rooms.room_number', 'rooms.type', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('rooms.id', 'rooms.room_number', 'rooms.type')
            ->orderByDesc('usage_count')
            ->limit(5)->get();

        return [
            'popular_types'     => $popularTypes,
            'maintenance_rooms' => $maintenanceRooms,
            'most_used_rooms'   => $mostUsedRooms,
        ];
    }
}