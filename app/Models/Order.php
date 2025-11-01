<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'product',      // JSON string
        'datetime',
        'totalprice',
        'promotion',
        'status',       // enum: in cart|wait|payment|shipping|complete
        'user_id',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'product'  => 'array',   // เก็บใน TEXT/JSON ก็ cast เป็น array ให้ frontend ใช้ง่าย
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
