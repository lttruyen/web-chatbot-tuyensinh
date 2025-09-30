<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class apiKey extends Model
{
    use HasFactory;

    protected $table = 'api_key';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'key_name',
        'mac_dinh',
    ];

    protected $casts = [
        'id'         => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
