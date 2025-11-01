<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable =[
        "title",
        "cost",
        "category",
        "stock",
        "user_id",
        "banner_image"
    ];
}
