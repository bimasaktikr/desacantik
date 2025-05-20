<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseProject extends Model
{
    //

    // add fillable
    protected $fillable = ['name', 'file_path'];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];
}
