<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThongTinNguoiDung extends Model
{
    use HasFactory;
    protected $table = 'thong_tin_nguoi_dung';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_cuoc_hoi_thoai',
        'ten_nguoi_dung',
        'email',
        'so_dien_thoai',
        'dia_chi',
        'nam_sinh',
        'created_at'
    ];
    public function cuocHoiThoai()
    {
        return $this->belongsTo(CuocHoiThoai::class, 'id_cuoc_hoi_thoai');
    }
}
