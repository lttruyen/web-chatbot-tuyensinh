<?php
use App\Http\Controllers\AccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
// Phần xem biểu đồ 
Route::prefix('access')->group(function () {
    // Trang quản lý truy cập
    Route::get('/', [AccessController::class, 'index'])->name('access.index')->middleware('session.role:admin,dev');
    // Trang xem log câu hỏi thường dùng và gần giống nhau 
    Route::get('/log', [AccessController::class, 'log'])->name('access.log')->middleware('session.role:admin,dev');
});