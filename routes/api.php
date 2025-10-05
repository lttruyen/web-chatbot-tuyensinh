<?php

use App\Http\Controllers\settingController;
use App\Http\Controllers\ApiKeyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

// Lấy danh sách smtp 
Route::get('/smtp', [controlController::class, 'getSmtp']);
// Lấy danh sách api-key 
Route::get('/api-key', [controlController::class, 'getApikey']);

//===============API xử lý người dùng =============================
// 1. Lưu thông tin người dùng 
Route::post('/luu-nguoi-dung', [nguoiDungController::class, 'store']);
// 2. Xuất thông tin người dùng ra file excel 
Route::get('/xuat-nguoi-dung', [nguoiDungController::class, 'export']);
// 3. Lấy danh sách câu hỏi theo người dùng 
Route::get('/cuoc-hoi-thoai', [nguoiDungController::class, 'showLog'])->name('api.log.cauhoi');

//================ API xử lý email ===============================
// Xử lý gửi email
Route::post('/gui-email', [EmailController::class, 'sendBulk'])->name('api.gui-email');

// ============= API xử lý smtp ================================
// Thêm mới tài khoản 
Route::post('/add-ac', [controlController::class, 'addAccount'])->name('api.account.add');
// Phần thêm mới smtp
Route::post('/smtp', [settingController::class, 'store']);
// Phần cập nhật smtp
Route::post('/smtp/update', [settingController::class, 'updateByRequest']);
// Xóa smtp 
Route::delete('smtp/{id}', [settingController::class, 'destroy']);

// ============= API xử lý apikey ===============================
// Phần thêm mới api-key
Route::post('/api-key', [ApiKeyController::class, 'store']);
// Phần cập nhật api-key 
Route::post('/api-key/update', [ApiKeyController::class, 'update']);
// Xóa api-key 
Route::delete('/api-key/{id}', [ApiKeyController::class, 'destroy']);

// ============= API xử lý câu hỏi và tài khoản==================
// 1. Thêm mới câu hỏi, câu trả lời 
Route::post('/add-qa', [ApiXuLyController::class, 'add']);
// 2. Cập nhật câu hỏi, câu trả lời
Route::post('/update-qa', [ApiXuLyController::class, 'update']);
// 3. Xóa câu hỏi, câu trả lời
Route::delete('/xoa-qa/{id1}/{id2}', [ApiXuLyController::class, 'deleteCauHoi']);