<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('products')
    ->name('products.')
    ->middleware(['auth:sanctum'])
    ->group(function() {
        Route::get('/', [ProductController::class, 'index'])->name('index');

        Route::middleware('is_admin')
            ->group(function() {
                Route::get('/create', [ProductController::class, 'create'])
                    ->name('create');
                Route::post('/', [ProductController::class, 'store'])
                    ->name('store');
                Route::get('products/{product}/edit', [ProductController::class, 'edit'])
                    ->name('edit');
                Route::put('products/{product}', [ProductController::class, 'update'])
                    ->name('update');
                Route::delete('products/{product}', [ProductController::class, 'destroy'])
                    ->name('destroy');
            });
    });

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';
