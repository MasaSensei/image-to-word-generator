<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $fillable = [
        'owner_token',
        'file_name',
        'file_path',
        'image_count',
    ];
}
