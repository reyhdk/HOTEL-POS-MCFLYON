<?php

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
// RUTE PUBLIK (TIDAK PERLU LOGIN)
// ==================================================
Route::prefix('public')->group(function () {
    Route::get('/available-rooms', [RoomController::class, 'getAvailableRooms']);
    Route::get('/room-details/{room}', [RoomController::class, 'showPublic']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/facilities', [FacilityController::class, 'index']);
});

Route::get('/setting', [SettingController::class, 'index']);

// Autentikasi
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// ==================================================
// RUTE TERAUTENTIKASI (PERLU LOGIN)
// ==================================================
Route::post('/midtrans/notification', [MidtransController::class, 'handleNotification']);
Route::middleware('auth:api')->group(function () {

    // Auth & User Profile
    Route::prefix('auth')->group(function () {
        Route::delete('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::get('/my-bookings', [UserBookingController::class, 'index']);
    Route::get('/my-check-in-status', [UserCheckInStatusController::class, 'getStatus']);

    // --- RUTE KHUSUS TAMU ---
    Route::prefix('guest')->name('guest.')->group(function () {
        Route::get('/profile', [GuestOrderController::class, 'getProfile']);
        Route::get('/menu', [MenuController::class, 'index']);
        Route::post('/orders', [GuestOrderController::class, 'store']);
        Route::get('/orders', [GuestOrderController::class, 'getOrderHistory']);
        Route::get('/orders/{order}', [GuestOrderController::class, 'show']);
        Route::post('/orders/{order}/pay', [GuestOrderController::class, 'processPayment']);
        Route::post('/service-requests', [ServiceRequestController::class, 'store']);
        Route::get('/service-requests', [ServiceRequestController::class, 'index']);
        Route::get('/folio', [CheckoutController::class, 'getFolio']); // Perbaikan minor: Impor controller di atas
        Route::post('/checkout', [CheckoutController::class, 'processCheckout']); // Perbaikan minor
    });

    // --- RUTE PANEL ADMIN (DILINDUNGI DENGAN PERMISSION) ---

    Route::post('/setting', [SettingController::class, 'update'])->middleware('can:edit settings');

    Route::middleware('can:view dashboard')->group(function () {
        Route::get('/dashboard-stats', [DashboardController::class, 'getStats']);
        Route::get('/sales-chart-data', [DashboardController::class, 'getSalesChartData']);
    });

    // POS & Pesanan
    Route::post('/orders', [OrderController::class, 'store'])->middleware('can:create pos_orders');
    Route::get('/pending-orders', [PaymentController::class, 'getPendingOrders'])->middleware('can:manage payments');
    Route::get('/transaction-history', [PaymentController::class, 'getTransactionHistory'])->middleware('can:view transaction_history');
    Route::post('/orders/{order}/pay', [PaymentController::class, 'processPayment'])->middleware('can:manage payments');
    Route::post('/orders/{order}/cancel', [PaymentController::class, 'cancelOrder'])->middleware('can:manage payments');
    Route::get('/folios', [FolioController::class, 'index'])->middleware('can:view folios');
    Route::post('/folios/{room}/checkout', [FolioController::class, 'processFolioPaymentAndCheckout'])->middleware('can:manage payments');

    // Manajemen Pesanan Online
    Route::get('/online-orders', [AdminOrderController::class, 'index'])->middleware('can:view online_orders');
    Route::get('/online-orders/{order}', [AdminOrderController::class, 'show'])->middleware('can:view online_orders');
    Route::patch('/online-orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->middleware('can:view online_orders');


    // Manajemen Permintaan Layanan (Untuk Staf)
    Route::middleware('can:manage service_requests')->group(function () {
        Route::get('/admin/service-requests', [AdminServiceRequestController::class, 'index']);
        Route::patch('/admin/service-requests/{serviceRequest}/status', [AdminServiceRequestController::class, 'updateStatus']);
    });

    // Check-in & Check-out
    Route::post('/check-in', [CheckInController::class, 'store'])->middleware('can:create pos_orders');
    Route::post('/check-out/{room}', [CheckInController::class, 'checkout'])->middleware('can:create pos_orders');
    Route::get('/admin/checkout-history', [CheckoutHistoryController::class, 'index'])->middleware('can:view checkout_history');


    // Manajemen Master Data
    Route::apiResource('menus', MenuController::class)->middleware('can:view menus');
    Route::apiResource('rooms', RoomController::class)->middleware('can:view rooms');
    Route::apiResource('facilities', FacilityController::class)->middleware('can:view facilities');
    Route::apiResource('guests', GuestController::class)->middleware('can:view guests');

    Route::post('/midtrans/create-transaction', [MidtransController::class, 'createTransaction']);


    Route::prefix('master')->group(function () {
        Route::get('/all-roles', [UserController::class, 'getAllRoles'])->middleware('can:view roles');
        Route::apiResource('users', UserController::class)->scoped(['user' => 'uuid'])->middleware('can:view users');
        Route::apiResource('roles', RoleController::class)->middleware('can:view roles');
    });
});