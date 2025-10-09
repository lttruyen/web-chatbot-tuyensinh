<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class TaiKhoan extends Model
{
    use HasFactory;

    // Tên bảng
    protected $table = 'tai_khoan';

    // Khóa chính
    protected $primaryKey = 'id';

    // Cho phép auto-increment
    public $incrementing = true;

    // Kiểu dữ liệu khóa chính
    protected $keyType = 'int';

    // Cho phép timestamps (Laravel sẽ dùng created_at, updated_at)
    public $timestamps = true;

    // Các cột có thể gán hàng loạt
    protected $fillable = [
        'username',
        'password',
        'ho_ten',
        'quyen',
    ];
}
