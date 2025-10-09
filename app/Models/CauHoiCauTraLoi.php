<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CauHoiCauTraLoi extends Model
{
    use HasFactory;
    protected $table = 'cau_hoi_cau_tra_loi';
    public $timestamps = true;

    protected $fillable = [
        'id_cau_hoi',
        'id_cau_tra_loi',
        'created_at'
    ];
    public function cauHoi()
    {
        return $this->belongsTo(CauHoi::class, 'id_cau_hoi');
    }

    public function cauTraLoi()
    {
        return $this->belongsTo(CauTraLoi::class, 'id_cau_tra_loi');
    }
}
