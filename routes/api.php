<?php

use App\Http\Controllers\ApiDuLieuController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\ApiXuLyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\nguoiDungController;
use App\Http\Controllers\settingController;

//============== Phần chat với bot  ==================
// 1. Gửi câu hỏi đến bot và nhận câu trả lời 
Route::post('/chat', [ChatController::class, 'chat']);
// 2. Tự lưu câu hỏi tạm nếu AI tự động trả lời 
Route::delete('/cau-tra-loi-tam/{id}', [ChatController::class, 'deleteTLTam']);



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
// 1. Thêm mới smtp
Route::post('/smtp', [settingController::class, 'store']);
// 2. Cập nhật smtp
Route::post('/smtp/update', [settingController::class, 'update']);
// 3. Xóa smtp
Route::delete('smtp/{id}', [settingController::class, 'destroy']);



// ============= API xử lý apikey ===============================
// 1. Thêm mới api-key 
Route::post('/api-key', [ApiKeyController::class, 'store']);
// 2. Cập nhật api-key 
Route::post('/api-key/update', [ApiKeyController::class, 'update']);
// 3. Xóa api-key
Route::delete('/api-key/{id}', [ApiKeyController::class, 'destroy']);



// ============= API xử lý câu hỏi và tài khoản==================
// 1. Thêm mới câu hỏi, câu trả lời 
Route::post('/add-qa', [ApiXuLyController::class, 'add']);
// 2. Cập nhật câu hỏi, câu trả lời
Route::post('/update-qa', [ApiXuLyController::class, 'update']);
// 3. Xóa câu hỏi, câu trả lời
Route::delete('/xoa-qa/{id1}/{id2}', [ApiXuLyController::class, 'deleteCauHoi']);
// 4. Thêm mới tài khoản
Route::post('/add-ac', [ApiXuLyController::class, 'addAccount'])->name('api.account.add');
// 5. Cập nhật tài khoản 
Route::post('/luu-account', [ApiXuLyController::class, 'updateAccount']);
// 6. Xóa tài khoản
Route::delete('/tai-khoan/{id}', [ApiXuLyController::class, 'destroy']);



// ============== API Lấy dữ liệu ================================
// 1. Lấy danh sách câu hỏi, câu trả lời
Route::get('/export-qa', [ApiDuLieuController::class, 'getCauHoi']);
// 2. Lấy danh sách log câu hỏi gần giống nhau 
Route::get('/log-cau-hoi', [ApiDuLieuController::class, 'getLogCauHoi']);
// 3. Lấy danh sách câu trả lời tạm
Route::get('/cau-tra-loi-tam', [ApiDuLieuController::class, 'getCauTraLoiTam']);
// 4. Lấy danh sách thông tin người dùng
Route::get('/nguoi-dung', [ApiDuLieuController::class, 'getUserInfor']);
// 5. Lấy danh sách tài khoản
Route::get('/tai-khoan', [ApiDuLieuController::class, 'getAccount']);
// 6. Lấy danh sách SMTP
Route::get('/smtp', [ApiDuLieuController::class, 'getSmtp']);
// 7. Lấy danh sách API-KEY
Route::get('/api-key', [ApiDuLieuController::class, 'getApikey']);
// 8. Lấy danh sách truy cập
Route::get('/access', [ApiDuLieuController::class, 'getAccess']);
// 9. Lọc câu hỏi theo người dùng
