<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Controller Imports ---
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
// API Controllers
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\FolioController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserBookingController;
use App\Http\Controllers\Api\UserCheckInStatusController;
use App\Http\Controllers\Api\Warehouse\WarehouseItemController;
use App\Http\Controllers\Api\Warehouse\StockTransactionController;
use App\Http\Controllers\Api\Warehouse\WarehouseCategoryController;
use App\Http\Controllers\Api\ChefController; // Import ChefController
use App\Http\Controllers\Api\Laundry\LaundryController;
// use App\Http\Controllers\Api\Laundry\LaundryMachineController;
use App\Http\Controllers\Api\Laundry\LaundryOrderController;
use App\Http\Controllers\Api\Laundry\LaundryServiceController;
// Guest & Admin Namespace Controllers
use App\Http\Controllers\Api\ServiceItemController;
use App\Http\Controllers\Api\Restopos\TableController;
use App\Http\Controllers\Api\Restopos\TableTypeController;
use App\Http\Controllers\Api\Admin\CheckoutHistoryController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\Api\Guest\CheckoutController;
use App\Http\Controllers\Api\Guest\GuestOrderController;
use App\Http\Controllers\Api\Guest\ServiceRequestController;
use App\Http\Controllers\Api\CashFlowController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==================================================
// 1. RUTE PUBLIK (TIDAK PERLU LOGIN)
// ==================================================
Route::prefix('public')->group(function () {
    Route::get('/available-rooms', [RoomController::class, 'getAvailableRooms']);
    Route::get('/room-details/{room}', [RoomController::class, 'showPublic']);
    Route::post('/bookings', [BookingController::class, 'store']); // Booking Online Tamu
    Route::get('/facilities', [FacilityController::class, 'index']);
    Route::get('/menus', [MenuController::class, 'index']);
});

// Settings Public (Hati-hati, pastikan controller memfilter data sensitif)
Route::get('/settings', [SettingController::class, 'index']);

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Midtrans Webhook (Wajib Public)
Route::post('/midtrans/notification', [MidtransController::class, 'handleNotification']);


// ==================================================
// 2. RUTE TERAUTENTIKASI (PERLU LOGIN) - JWT
// ==================================================
Route::middleware('auth:api')->group(function () {

    // --- A. USER / TAMU (Aplikasi Sisi User) ---

    // 1. Profile & Auth
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('auth')->group(function () {
        Route::delete('logout', [AuthController::class, 'logout']);
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::get('/user/profile-ktp', [GuestController::class, 'getMyProfile']);
    Route::post('/user/update-ktp', [GuestController::class, 'selfUpdateKtp']);
    

    // 2. Booking & Status (Sisi Tamu)
    Route::get('/my-bookings', [UserBookingController::class, 'index']);
    Route::get('/user/check-in-status', [UserCheckInStatusController::class, 'getStatus']);

    // Create Transaction (Jika user ingin bayar manual via button di aplikasi)
    Route::post('/midtrans/create-transaction', [MidtransController::class, 'createTransaction']);

    // 3. Guest Mode (Saat Tamu Menginap)
    Route::prefix('guest')->name('guest.')->group(function () {
        Route::get('/menu', [MenuController::class, 'index']);

        Route::get('/profile', [GuestOrderController::class, 'getProfile']);
        Route::post('/orders', [GuestOrderController::class, 'store']);
        Route::get('/orders', [GuestOrderController::class, 'getOrderHistory']);
        Route::get('/orders/{order}', [GuestOrderController::class, 'show']);
        Route::post('/orders/{order}/pay', [GuestOrderController::class, 'processPayment']);

        Route::post('/service-requests', [ServiceRequestController::class, 'store']);
        Route::get('/service-requests', [ServiceRequestController::class, 'index']);
        Route::get('/service-items', [ServiceItemController::class, 'index']);

        Route::get('/folio', [CheckoutController::class, 'getFolio']);
        Route::post('/checkout', [CheckoutController::class, 'processCheckout']);
    });

    Route::middleware('can:manage service_requests')->group(function () {

    // CRUD Master Item
        Route::apiResource('admin/service-items', ServiceItemController::class);

        // Endpoint tambahan
        Route::get('/admin/service-items-categories', [ServiceItemController::class, 'categories']);
        Route::get('/admin/service-items-staff',      [ServiceItemController::class, 'eligibleStaff']);

        // Update status request (sudah ada, pastikan ada)
        Route::patch('/admin/service-requests/{serviceRequest}/status', [AdminServiceRequestController::class, 'updateStatus']);
        Route::patch('/admin/service-requests/{serviceRequest}/assign', [AdminServiceRequestController::class, 'assignStaff']);
    });


    // --- B. ADMIN / STAFF (Backoffice Dashboard) ---

    // Dashboard Stats
    Route::middleware('can:view dashboard')->group(function () {
        Route::get('/dashboard-stats', [DashboardController::class, 'getStats']);
        Route::get('/sales-chart-data', [DashboardController::class, 'getSalesChartData']);

        Route::get('/reports/hotel', [ReportController::class, 'hotelReport']);
        Route::get('/reports/hotel/export', [ReportController::class, 'exportHotelReport']);
    });

    Route::get('/cash-flow/export', [CashFlowController::class, 'export']);
    Route::get('/cash-flow', [CashFlowController::class, 'index']);
    Route::post('/cash-flow', [CashFlowController::class, 'store']);

    // Settings Update
    Route::post('/settings', [SettingController::class, 'update'])->middleware('can:edit settings');

    // Manajemen Tamu
    Route::middleware('can:view guests')->group(function () {
        Route::post('/guests/{id}/verify-ktp', [GuestController::class, 'verifyKtp']);
        Route::post('/guests/{id}/reject-ktp', [GuestController::class, 'rejectKtp']);
        Route::apiResource('guests', GuestController::class);

        // Verifikasi Booking (Fitur Baru)
        Route::post('/bookings/{id}/verify', [BookingController::class, 'verifyBooking']);
        Route::post('/bookings/{id}/reject', [BookingController::class, 'rejectBooking']);
    });

    // Manajemen Booking (General)
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);

    // Operasional Check-In / Check-Out
    // [PENTING] Saya bungkus middleware permission agar user biasa tidak bisa akses
    Route::middleware('can:create pos_orders')->group(function () {
        Route::post('/check-in', [CheckInController::class, 'store']);
        Route::post('/check-in/process-booking', [CheckInController::class, 'storeFromBooking']);

        // Koreksi: Menggunakan storeDirect (karena storeWalkIn tidak ada di controller)
        Route::post('/check-in/walk-in', [CheckInController::class, 'storeDirect']);
        Route::post('/admin/check-ins/store-direct', [CheckInController::class, 'storeDirect']);

        Route::post('/check-out/{room}', [CheckInController::class, 'checkout']);

        Route::get('/pos/tables', [TableController::class, 'index']);
        Route::post('/pos/tables', [TableController::class, 'store']); // Create Table (Potong Stok)
        Route::put('/pos/tables/{id}', [TableController::class, 'update']);
        Route::delete('/pos/tables/{id}', [TableController::class, 'destroy']);
        
        // Table Types Management
        Route::get('/pos/table-types', [TableTypeController::class, 'index']);
        Route::post('/pos/table-types', [TableTypeController::class, 'store']);
        Route::put('/pos/table-types/{id}', [TableTypeController::class, 'update']);
        Route::delete('/pos/table-types/{id}', [TableTypeController::class, 'destroy']);
        
        // Route Baru: Repair/Ganti Sparepart
        Route::post('/pos/tables/{id}/replace-item', [TableController::class, 'replaceItem']);
        // -------------------------------

        Route::get('/pos/online-orders-count', [AdminOrderController::class, 'getPendingCount']);
        Route::get('/pos/occupied-rooms', [RoomController::class, 'getOccupiedRoomsForPos']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::prefix('kitchen')->group(function () {
            // Queue & Orders
            Route::get('/queue', [KitchenController::class, 'getQueueStatus']);
            Route::get('/orders', [KitchenController::class, 'getTodayOrders']);
            
            // Chef Assignment (Sekarang pakai database)
            Route::get('/chefs', [ChefController::class, 'getAvailableChefs']); // ✅ Updated
            Route::post('/orders/{order}/assign', [KitchenController::class, 'assignChef']);
            Route::patch('/orders/{order}/status', [KitchenController::class, 'updateStatus']);
        });

        // ✅ NEW: Chef Management Routes (Admin Only)
        Route::prefix('chefs')->group(function () {
            Route::get('/', [ChefController::class, 'index']); // List all chefs
            Route::get('/statistics', [ChefController::class, 'statistics']); // Stats
            Route::get('/eligible-users', [ChefController::class, 'getEligibleUsers']); // Users yang bisa jadi chef
            Route::post('/', [ChefController::class, 'store']); // Add chef
            Route::put('/{id}', [ChefController::class, 'update']); // Update chef
            Route::patch('/{id}/toggle-status', [ChefController::class, 'toggleStatus']); // Aktif/nonaktif
            Route::delete('/{id}', [ChefController::class, 'destroy']); // Delete chef
        });
    });

    Route::prefix('guest/laundry')->group(function () {
        Route::get('/services', [LaundryController::class, 'getServices']); // Lihat daftar harga
        Route::post('/request', [LaundryController::class, 'requestLaundry']); // Tamu klik request
        Route::get('/my-requests', [LaundryController::class, 'myRequests']); // Status laundry tamu
    });

    // Untuk Petugas / Admin Laundry
    Route::prefix('admin/laundry')->middleware('can:manage laundry')->group(function () {
        // Master Data & Setting
        // Route::apiResource('machines', LaundryMachineController::class);
        Route::apiResource('services', LaundryServiceController::class);
        
        // Integrasi Estimasi Gudang
        Route::post('/services/{id}/materials', [LaundryServiceController::class, 'setMaterials']);
        
        // Operasional (User Request Laundry)
        Route::get('/orders', [LaundryOrderController::class, 'index']); // List request tamu
        Route::post('/orders/{id}/pickup', [LaundryOrderController::class, 'pickup']); // Petugas ambil
        Route::post('/orders/{id}/process', [LaundryOrderController::class, 'process']); // Mulai cuci (disini auto-potong stok)
        Route::post('/orders/{id}/deliver', [LaundryOrderController::class, 'deliver']); // Selesai
    });

    Route::prefix('warehouse')->middleware('auth:api')->group(function () {
        
        // 1. Master Data Kategori (Baru)
        Route::apiResource('categories', WarehouseCategoryController::class);
        Route::post('categories/update-icons', [WarehouseCategoryController::class, 'updateIcons']);
        // 2. Items
        Route::get('items/next-code', [WarehouseItemController::class, 'getNextCode']); // Endpoint Baru Auto Code
        Route::get('items/low-stock', [WarehouseItemController::class, 'getLowStock']);
        Route::get('items/stock-value', [WarehouseItemController::class, 'getStockValue']);
        Route::get('items/categories', [WarehouseItemController::class, 'getCategories']);
        Route::post('items/{id}/adjust-stock', [WarehouseItemController::class, 'adjustStock']);
        Route::apiResource('items', WarehouseItemController::class);
        
        // 3. Transactions
        Route::get('/transactions/export', [StockTransactionController::class, 'export']);
        Route::get('transactions/export-laporan', [StockTransactionController::class, 'exportLaporan']);
        Route::post('transactions/bulk', [StockTransactionController::class, 'bulkStore']);
        Route::get('transactions/summary', [StockTransactionController::class, 'getSummary']);
        Route::apiResource('transactions', StockTransactionController::class)->except(['update']);
    });

    Route::get('/admin/checkout-history', [CheckoutHistoryController::class, 'index'])->middleware('can:view checkout_history');

    // Debugging
    Route::get('/debug-check-in-status', [UserCheckInStatusController::class, 'debugStatus']);

    // POS & Transaksi
    Route::post('/orders', [OrderController::class, 'store'])->middleware('can:create pos_orders');

    Route::middleware('can:manage payments')->group(function () {
        Route::get('/pending-orders', [PaymentController::class, 'getPendingOrders']);
        Route::post('/orders/{order}/pay', [PaymentController::class, 'processPayment']);
        Route::post('/orders/{order}/cancel', [PaymentController::class, 'cancelOrder']);
        Route::post('/folios/{room}/checkout', [FolioController::class, 'processFolioPaymentAndCheckout']);

        // Admin Online Orders Payment
        Route::post('/admin/orders/{order}/pay', [AdminOrderController::class, 'markAsPaid']);
    });

    Route::middleware('can:view transaction_history')->group(function () {
        Route::get('/transaction-history', [PaymentController::class, 'getTransactionHistory']);
        Route::get('/transaction-history/export', [PaymentController::class, 'exportReport']);
    });

    Route::get('/folios', [FolioController::class, 'index'])->middleware('can:view folios');
    Route::get('/pos/occupied-rooms', [RoomController::class, 'getOccupiedRoomsForPos'])->middleware('can:create pos_orders');

    // Online Orders Management
    Route::middleware('can:view online_orders')->group(function () {
        Route::get('/online-orders', [AdminOrderController::class, 'index']);
        Route::get('/online-orders/{order}', [AdminOrderController::class, 'show']);
        Route::put('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
    });

    // Service Requests Admin
    Route::middleware('can:manage service_requests')->group(function () {
        Route::get('/admin/service-requests', [AdminServiceRequestController::class, 'index']);
        Route::patch('/admin/service-requests/{serviceRequest}/status', [AdminServiceRequestController::class, 'updateStatus']);
    });

    Route::get('/rooms/cleaning-tasks', [RoomController::class, 'getCleaningTasks'])
        ->middleware('can:manage cleaning status');

    // Master Data
    Route::apiResource('menus', MenuController::class)->middleware('can:view menus');
    Route::apiResource('facilities', FacilityController::class)->middleware('can:view facilities');
    Route::apiResource('rooms', RoomController::class)->middleware('can:view rooms');

    // Room Cleaning
    Route::middleware('can:manage cleaning status')->group(function () {
        Route::post('/rooms/{room}/request-cleaning', [RoomController::class, 'requestCleaning']);
        Route::post('/rooms/{room}/mark-as-clean', [RoomController::class, 'markAsClean']);
    });

    // [Info] Checkout juga butuh permission view rooms atau permission khusus
    Route::post('/rooms/{room}/checkout', [RoomController::class, 'checkout'])->middleware('can:view rooms');

    // User & Role Management
    Route::prefix('master')->group(function () {
        Route::get('/all-roles', [UserController::class, 'getAllRoles'])->middleware('can:view roles');
        Route::apiResource('users', UserController::class)->middleware('can:view users');
        Route::apiResource('roles', RoleController::class)->middleware('can:view roles');
        Route::get('/permissions', [RoleController::class, 'getAllPermissions'])->middleware('can:view roles');
    });
});
