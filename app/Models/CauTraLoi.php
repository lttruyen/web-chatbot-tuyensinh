<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CauTraLoi extends Model
{
    use HasFactory;
    protected $table = 'cau_tra_loi';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'cau_tra_loi',
        'created_at'
    ];
    public function cauHoiCauTraLoi()
    {
        return $this->hasMany(CauHoiCauTraLoi::class, 'id_cau_tra_loi');
    }
}
