<?php

use Illuminate\Support\Facades\DB; // Pastikan use DB ini ada
use Illuminate\Support\Facades\Route;

// ==========================================================
// TAMBAHKAN BLOK KODE INI UNTUK DEBUGGING
Route::get('/cek-db', function () {
    try {
        $dbName = DB::connection()->getDatabaseName();
        return 'Aplikasi web ini berhasil terhubung ke database: ' . $dbName;
    } catch (\Exception $e) {
        return 'GAGAL terhubung ke database. Error: ' . $e->getMessage();
    }
});
// ==========================================================


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api\/)[\/\w\.-]*');