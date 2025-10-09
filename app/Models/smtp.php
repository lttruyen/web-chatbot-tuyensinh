<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class smtp extends Model
{
    use HasFactory;
    protected $table = 'smtp';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'smtp',       // tài khoản/username/email SMTP
        'matkhau',    // mật khẩu SMTP
        'mac_dinh',   // 0/1: có phải cấu hình mặc định không
    ];
    protected $casts = [
        'mac_dinh' => 'boolean',
    ];
}
