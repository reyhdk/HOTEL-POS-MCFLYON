<?php 

 use Illuminate\Support\Facades\Route; 
 // --- Controller Imports --- 
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
 use App\Http\Controllers\Api\Guest\GuestOrderController; 
 use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController; 



 /* |-------------------------------------------------------------------------- 
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

 // Autentikasi untuk ADMIN PANEL 
 Route::prefix('auth')->group(function () { 
     Route::post('login', [AuthController::class, 'login']); 
     Route::post('register', [AuthController::class, 'register']); 
 }); 



 Route::get('/my-bookings', [UserBookingController::class, 'index']); 
 Route::get('/setting', [SettingController::class, 'index']); 

 // ================================================== 
 // RUTE TERAUTENTIKASI (PERLU LOGIN) 
 // ================================================== 
 Route::middleware('auth:api')->group(function () { 

     // Auth & User Profile (Admin) 
     Route::prefix('auth')->group(function () { 
         Route::delete('logout', [AuthController::class, 'logout']); 
         Route::get('me', [AuthController::class, 'me']); 
     }); 

     // ================================================== 
     // --- RUTE KHUSUS TAMU --- (SETELAH TAMU LOGIN) 
     // ================================================== 
  Route::prefix('guest')->name('guest.')->group(function () { 
         Route::get('/profile', [GuestOrderController::class, 'getProfile']); 
         Route::get('/menu', [MenuController::class, 'index']); 
         Route::post('/orders', [GuestOrderController::class, 'store']); 
         Route::get('/orders', [GuestOrderController::class, 'getOrderHistory']); 
         Route::get('/orders/{order}', [GuestOrderController::class, 'show']); 
         Route::post('/orders/{order}/pay', [GuestOrderController::class, 'processPayment']); 
     }); 

     // --- RUTE KHUSUS ADMIN --- 
     Route::middleware('role:admin')->group(function () { 
         // Dashboard 
         Route::get('/dashboard-stats', [DashboardController::class, 'getStats']); 
         Route::get('/sales-chart-data', [DashboardController::class, 'getSalesChartData']); 

         // Menu Management 
         Route::apiResource('menus', MenuController::class); 

         // Room Management & Cleaning 
         Route::apiResource('rooms', RoomController::class); 
         Route::post('/rooms/{room}/mark-for-cleaning', [RoomController::class, 'markForCleaning']); 
         Route::post('/rooms/{room}/mark-as-clean', [RoomController::class, 'markAsClean']); 
         Route::post('/rooms/{room}/request-cleaning', [RoomController::class, 'requestCleaning']); 

         // Facility Management 
         Route::apiResource('facilities', FacilityController::class); 

         // Guest Management 
         Route::apiResource('guests', GuestController::class); 

         // Check-in & Check-out 
         Route::post('/check-in', [CheckInController::class, 'store']); 
         Route::post('/check-out/{room}', [CheckInController::class, 'checkout']); 

         // Order, Payment, & Folio (POS Admin) 
         Route::post('/orders', [OrderController::class, 'store']); 
         Route::get('/pending-orders', [PaymentController::class, 'getPendingOrders']); 
         Route::get('/transaction-history', [PaymentController::class, 'getTransactionHistory']); 
         Route::post('/orders/{order}/pay', [PaymentController::class, 'processPayment']); 
         Route::post('/orders/{order}/cancel', [PaymentController::class, 'cancelOrder']); 
         Route::get('/folios', [FolioController::class, 'index']); 
         Route::post('/folios/{room}/checkout', [FolioController::class, 'processFolioPaymentAndCheckout']); 
         Route::get('/online-orders', [AdminOrderController::class, 'index']); 
         Route::get('/online-orders/{order}', [AdminOrderController::class, 'show']); // <-- TAMBAHKAN INI 
         // routes/api.php 

         Route::patch('/online-orders/{order}/status', [AdminOrderController::class, 'updateStatus']); // <-- TAMBAHKAN INI 

         // Settings, Users & Roles 
          Route::get('/all-roles', [UserController::class, 'getAllRoles']); // Untuk dropdown di form user 
         Route::apiResource('/master/users', UserController::class); 
         Route::apiResource('/master/roles', RoleController::class); 
         }); 
     });