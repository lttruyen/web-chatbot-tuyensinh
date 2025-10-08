<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CauHoi extends Model
{
    use HasFactory;
    protected $table = 'cau_hoi';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'cau_hoi',
        'embedding',
        'created_at'
    ];
    public function cauHoiCauTraLoi()
    {
        return $this->hasMany(CauHoiCauTraLoi::class, 'id_cau_hoi');
    }
}
