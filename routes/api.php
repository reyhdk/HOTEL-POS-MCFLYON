<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Controller Imports ---
use App\Http\Controllers\Api\Admin\CheckoutHistoryController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\FolioController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\UserBookingController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\Guest\ServiceRequestController;
use App\Http\Controllers\Api\Guest\GuestOrderController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\Api\Guest\CheckoutController;
use App\Http\Controllers\Api\UserCheckInStatusController;

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
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/facilities', [FacilityController::class, 'index']);
    Route::get('/menus', [MenuController::class, 'index']); // Menu bisa dilihat publik
});

// Settings Public (Logo, Nama Hotel, dll)
Route::get('/settings', [SettingController::class, 'index']);

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Midtrans Webhook (Wajib Public)
Route::post('/midtrans/notification', [MidtransController::class, 'handleNotification']);


// ==================================================
// 2. RUTE TERAUTENTIKASI (PERLU LOGIN)
// ==================================================
Route::middleware('auth:api')->group(function () {

    // --- User Profile ---
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('auth')->group(function () {
        Route::delete('logout', [AuthController::class, 'logout']);
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // --- Booking & Status Tamu (Sisi User App) ---
    Route::get('/my-bookings', [UserBookingController::class, 'index']);
    Route::get('/my-check-in-status', [UserCheckInStatusController::class, 'getStatus']);
    Route::post('/midtrans/create-transaction', [MidtransController::class, 'createTransaction']);

    // --- RUTE GUEST MODE (Saat Tamu Login di Kamar) ---
    Route::prefix('guest')->name('guest.')->group(function () {
        Route::get('/profile', [GuestOrderController::class, 'getProfile']);
        Route::post('/orders', [GuestOrderController::class, 'store']);
        Route::get('/orders', [GuestOrderController::class, 'getOrderHistory']);
        Route::get('/orders/{order}', [GuestOrderController::class, 'show']);
        Route::post('/orders/{order}/pay', [GuestOrderController::class, 'processPayment']);
        Route::post('/service-requests', [ServiceRequestController::class, 'store']);
        Route::get('/service-requests', [ServiceRequestController::class, 'index']);
        Route::get('/folio', [CheckoutController::class, 'getFolio']);
        Route::post('/checkout', [CheckoutController::class, 'processCheckout']);
    });

    // ==================================================
    // 3. RUTE ADMIN / STAFF (Backoffice)
    // ==================================================

    // --- Dashboard & Settings ---
    Route::post('/settings', [SettingController::class, 'update'])->middleware('can:edit settings');
    Route::middleware('can:view dashboard')->group(function () {
        Route::get('/dashboard-stats', [DashboardController::class, 'getStats']);
        Route::get('/sales-chart-data', [DashboardController::class, 'getSalesChartData']);
    });

    // --- Manajemen Tamu (Guest) ---
    // [PENTING] Route Custom Verifikasi KTP harus DIBATAS resource guests
    Route::post('/guests/{id}/verify', [GuestController::class, 'verify'])->middleware('can:view guests');
    Route::post('/guests/{id}/reject-ktp', [GuestController::class, 'rejectKtp'])->middleware('can:view guests');
    Route::apiResource('guests', GuestController::class)->middleware('can:view guests');

    // --- Manajemen Booking ---
    Route::post('/bookings/{id}/verify', [BookingController::class, 'verifyBooking']);
    Route::post('/bookings/{id}/reject', [BookingController::class, 'rejectBooking']);
    Route::get('/bookings', [BookingController::class, 'index']);

    // --- Check-In / Check-Out Operasional ---
    Route::post('/check-in', [CheckInController::class, 'store'])->middleware('can:create pos_orders');
    Route::post('/check-in/process-booking', [CheckInController::class, 'storeFromBooking']); // Checkin Tamu Booking
    Route::post('/check-in/walk-in', [CheckInController::class, 'storeWalkIn']); // Checkin Tamu Walk-in

    Route::post('/check-out/{room}', [CheckInController::class, 'checkout'])->middleware('can:create pos_orders');
    Route::get('/admin/checkout-history', [CheckoutHistoryController::class, 'index'])->middleware('can:view checkout_history');

    // --- POS (Point of Sales) & Transaksi ---
    Route::post('/orders', [OrderController::class, 'store'])->middleware('can:create pos_orders');
    Route::get('/pending-orders', [PaymentController::class, 'getPendingOrders'])->middleware('can:manage payments');
    Route::post('/orders/{order}/pay', [PaymentController::class, 'processPayment'])->middleware('can:manage payments');
    Route::post('/orders/{order}/cancel', [PaymentController::class, 'cancelOrder'])->middleware('can:manage payments');

    Route::get('/transaction-history', [PaymentController::class, 'getTransactionHistory'])->middleware('can:view transaction_history');
    Route::get('/transaction-history/export', [PaymentController::class, 'exportReport'])->middleware('can:view transaction_history');

    Route::get('/folios', [FolioController::class, 'index'])->middleware('can:view folios');
    Route::post('/folios/{room}/checkout', [FolioController::class, 'processFolioPaymentAndCheckout'])->middleware('can:manage payments');
    Route::get('/pos/occupied-rooms', [RoomController::class, 'getOccupiedRoomsForPos'])->middleware('can:create pos_orders');

    // --- Online Orders (Dari Tamu di Kamar) ---
    Route::get('/online-orders', [AdminOrderController::class, 'index'])->middleware('can:view online_orders');
    Route::get('/online-orders/{order}', [AdminOrderController::class, 'show'])->middleware('can:view online_orders');
    Route::put('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->middleware('can:view online_orders');
    Route::post('/admin/orders/{order}/pay', [AdminOrderController::class, 'markAsPaid'])->middleware('can:manage payments');

    // --- Service Requests ---
    Route::middleware('can:manage service_requests')->group(function () {
        Route::get('/admin/service-requests', [AdminServiceRequestController::class, 'index']);
        Route::patch('/admin/service-requests/{serviceRequest}/status', [AdminServiceRequestController::class, 'updateStatus']);
    });

    // --- Master Data (CRUD) ---
    Route::apiResource('menus', MenuController::class)->middleware('can:view menus');
    Route::apiResource('facilities', FacilityController::class)->middleware('can:view facilities');

    Route::apiResource('rooms', RoomController::class)->middleware('can:view rooms');
    Route::post('/rooms/{room}/request-cleaning', [RoomController::class, 'requestCleaning'])->middleware('can:manage cleaning status');
    Route::post('/rooms/{room}/mark-as-clean', [RoomController::class, 'markAsClean'])->middleware('can:manage cleaning status');

    Route::prefix('master')->group(function () {
        Route::get('/all-roles', [UserController::class, 'getAllRoles'])->middleware('can:view roles');
        Route::apiResource('users', UserController::class)->scoped(['user' => 'uuid'])->middleware('can:view users');
        Route::apiResource('roles', RoleController::class)->middleware('can:view roles');
        Route::get('/permissions', [RoleController::class, 'getAllPermissions'])->middleware('can:view roles');
    });
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      });
