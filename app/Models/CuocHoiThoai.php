<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuocHoiThoai extends Model
{
    use HasFactory;
    protected $table = 'cuoc_hoi_thoai';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'danh_gia',
        'created_at',
        'updated_at',
        'token',
    ];
    
    public function logCauHoi()
    {
        return $this->hasMany(LogCauHoi::class, 'id_cuoc_hoi_thoai');
    }

    public function cauTraLoiTam()
    {
        return $this->hasMany(CauTraLoiTam::class, 'id_cuoc_hoi_thoai');
    }

    public function thongTinNguoiDung()
    {
        return $this->hasOne(ThongTinNguoiDung::class, 'id_cuoc_hoi_thoai');
    }
}
