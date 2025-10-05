<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
// Phần quản lý người dùng
Route::prefix('user')->group(function () {
    Route::get('/', [nguoiDungController::class, 'index'])->name('user.index')->middleware('session.role:admin,dev');
});