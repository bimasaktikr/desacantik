<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    //

    // add fillable
    protected $fillable = [
        'name',
        'slug',
        'regency_id',
        'code',
        'district_code',
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];
}
