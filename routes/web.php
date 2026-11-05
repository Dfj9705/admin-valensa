<?php

use App\Http\Controllers\ReportesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/products/print', [ReportesController::class, 'productos'])
    ->middleware(['web', 'auth', 'can:reportes.ver', 'can:reportes.exportar'])
    ->name('products.print');

Route::get('/reportes/servicios', [ReportesController::class, 'servicios'])
    ->middleware(['web', 'auth', 'can:reportes.ver', 'can:reportes.exportar'])
    ->name('reportes.servicios');
Route::get('/reportes/caja', [ReportesController::class, 'caja'])
    ->middleware(['web', 'auth', 'can:reportes.ver', 'can:reportes.exportar'])
    ->name('reportes.caja');
Route::get('/reportes/ventas-pendientes', [ReportesController::class, 'ventasPendientes'])
    ->middleware(['web', 'auth', 'can:reportes.ver', 'can:reportes.exportar'])
    ->name('reportes.ventas.pendientes');
Route::get('/reportes/catalogo', [ReportesController::class, 'catalogo'])
    ->middleware(['web', 'auth', 'can:reportes.ver', 'can:reportes.exportar'])
    ->name('catalogo.print');

Route::get('/reportes/deudores', [ReportesController::class, 'deudores'])
    ->middleware(['web', 'auth', 'can:reportes.ver', 'can:reportes.exportar'])
    ->name('reportes.deudores');
