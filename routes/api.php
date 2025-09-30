<?php

use App\Http\Controllers\settingController;
use App\Http\Controllers\ApiKeyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

// Lấy danh sách smtp 
Route::get('/smtp', [controlController::class, 'getSmtp']);
// Lấy danh sách api-key 
Route::get('/api-key', [controlController::class, 'getApikey']);

// Thêm mới tài khoản 
Route::post('/add-ac', [controlController::class, 'addAccount'])->name('api.account.add');
// Phần thêm mới smtp
Route::post('/smtp', [settingController::class, 'store']);
// Phần cập nhật smtp
Route::post('/smtp/update', [settingController::class, 'updateByRequest']);
// Xóa smtp 
Route::delete('smtp/{id}', [settingController::class, 'destroy']);

// Phần thêm mới api-key
Route::post('/api-key', [ApiKeyController::class, 'store']);
// Phần cập nhật api-key 
Route::post('/api-key/update', [ApiKeyController::class, 'update']);
// Xóa api-key 
Route::delete('/api-key/{id}', [ApiKeyController::class, 'destroy']);
