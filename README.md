
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



