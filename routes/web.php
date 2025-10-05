<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\ApiXuLyController;
use App\Http\Controllers\nguoiDungController;
use App\Http\Controllers\settingController;
use Illuminate\Support\Facades\Route;

// Trang chủ dùng để chat 
Route::get('/', function () {
    return view('index');
})->name('trangchu');

// Phần đăng nhập, đăng xuất 
Route::post('/admin/login', [ApiXuLyController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [ApiKeyController::class, 'logout'])->name('admin.logout');

// Phần cài đặt SMTP
Route::prefix('smtp')->group(function () {
    Route::get('/', [settingController::class, 'index'])->name('smtp.index')->middleware('session.role:admin,dev');
});

// Phần cài đặt API 
Route::prefix('api')->group(function () {
    Route::get('/', [ApiKeyController::class, 'index'])->name('api.index')->middleware('session.role:admin,dev');
});

// Phần quản lý người dùng
Route::prefix('user')->group(function () {
    Route::get('/', [nguoiDungController::class, 'index'])->name('user.index')->middleware('session.role:admin,dev');
});