
# Web Chatbot Tuyển Sinh

Dự án xây dựng hệ thống chatbot hỗ trợ tuyển sinh, phát triển trên nền tảng **Laravel**.

---

## 1. Cài đặt Laravel
- Cài đặt Laravel bằng Composer:  
  ```bash
  composer create-project laravel/laravel tvuchatbot

Cấu hình môi trường trong file .env (database, mail, key...).

Chạy server nội bộ:

php artisan serve

## 2. Xây dựng Database

Cấu hình kết nối trong file .env:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tvuchatbot
DB_USERNAME=root
DB_PASSWORD=

Tạo migration và migrate dữ liệu:

php artisan make:migration create_users_table
php artisan migrate

## 3. Tạo các model

Tạo model theo đường dẫn app/Models/:

php artisan make:model TênModel


## 4. Quản lý API key

- Controller: ApiKeyController.php:
+ Hàm index() dùng để gọi lại view;
+ Hàm store() dùng để xử lý thêm API key mới vào DB;
+ Hàm update() dùng để cập nhật API key;
+ Hàm destroy() dùng để xóa API key;
- View : api/index.blade.php
- route: 
- Route::prefix('api')->group(function () {
    Route::get('/', [ApiKeyController::class, 'index'])->name('api.index')->middleware('session.role:admin,dev');
}); // tuyến đường giao diện trang quản lý api
- api xử lý:
+ Route::post('/api-key', [ApiKeyController::class, 'store']); // Phần thêm mới api-key
+ Route::post('/api-key/update', [ApiKeyController::class, 'update']); // Phần cập nhật api-key 
+ Route::delete('/api-key/{id}', [ApiKeyController::class, 'destroy']); // Phần xóa api-key 


## 5. Quản lý SMTP

- Controller: settingController.php
+ Hàm index() dùng để gọi lại view;
+ Hàm store() dùng để thêm smtp;
+ Hàm updateByRequest() dùng để cập nhật smtp;
+ Hàm destroy() dùng để xóa smtp; 
- View: admin/smtp.blade.php
- route: Route::prefix('smtp')->group(function () {
    Route::get('/', [settingController::class, 'index'])->name('smtp.index')->middleware('session.role:admin,dev');
}); //tuyến đường giao diện trang quản lý api
- api xử lý: 
- Route::post('/smtp', [settingController::class, 'store']); // phần thêm smtp
- Route::post('/smtp/update', [settingController::class, 'updateByRequest']); // phần cập nhật smtp
- Route::delete('smtp/{id}', [settingController::class, 'destroy']); // Xóa smtp


## 6. Quản lý câu hỏi / trả lời

- Controller: ApiXuLyController.php
+ Ngoại trừ các hàm dành cho quản lý tài khoản truy cập, còn lại điều hỗ trợ cho chức năng quản lý câu hỏi và câu trả lời.
- View: account/chat.blade.php
- route:
- api xử lý:
+ Route::post('/add-qa', [ApiXuLyController::class, 'add']); // Thêm mới câu hỏi, câu trả lời 
+ Route::post('/update-qa', [ApiXuLyController::class, 'update']);  // Cập nhật câu hỏi, câu trả lời
Route::delete('/xoa-qa/{id1}/{id2}', [ApiXuLyController::class, 'deleteCauHoi']); // Xóa câu hỏi, câu trả lời


## 7. Giao diện trang Chatbot

- Controller: ChatController.php
Cuối controller này có hàm deleteTLTam dùng để xóa câu trả lời tạm 
- View: index.blade.php file ở ngoài cùng
- route:
Route::get('/', function () {return view('index');})->name('trangchu'); trang chủ giao diện chatbot
- api xử lý:
+ Route::post('/chat', [ChatController::class, 'chat']); //xử lý chat
+ Route::delete('/cau-tra-loi-tam/{id}', [ChatController::class, 'deleteTLTam']); // xử lý xóa câu trả lời tạm


## 8. Quản lý người dùng

- Controller: nguoiDungController;
+ Hàm index() dùng để gọi lại view;
+ Hàm store() dùng để thêm mới người dùng hàm này dùng ngay giao diện chat của chat bot;
+ Hàm export() dùng để export file excel thông tin người dùng.
- View: admin/user.blade.php
- route: Route::prefix('user')->group(function () {
    Route::get('/', [nguoiDungController::class, 'index'])->name('user.index')->middleware('session.role:admin,dev');
}); Xử lý lưu thông tin người dùng
- api xử lý: 
+ Route::get('/xuat-nguoi-dung', [nguoiDungController::class, 'export']); // xuất thông tin người dùng sang file excel
+ Route::post('/luu-nguoi-dung', [nguoiDungController::class, 'store']); //xử lý lưu thông tin người dùng
+ Route::get('/cuoc-hoi-thoai', [nguoiDungController::class, 'showLog'])->name('api.log.cauhoi'); // Lọc câu hỏi theo người dùng 


## 9. Gửi email

- Controller: EmailController.php
+ Hàm sendBulk(): dùng để xử lý gửi mail, nhận request từ trang quản lý người dùng;
- api xử lý: 
- Route::post('/gui-email', [EmailController::class, 'sendBulk'])->name('api.gui-email'); //api gửi mail 


