<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CauTraLoiTam extends Model
{
    //
    use HasFactory;
    protected $table = 'cau_tra_loi_tam';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'id_cuoc_hoi_thoai',
        'cau_hoi',
        'cau_tra_loi',
        'created_at'
    ];
    public function cuocHoiThoai()
    {
        return $this->belongsTo(CuocHoiThoai::class, 'id_cuoc_hoi_thoai');
    }
}
