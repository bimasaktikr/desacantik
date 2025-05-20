<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assigment extends Model
{
    //

    // add fillable
    protected $fillable = [
        'user_id',
        'area'
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}
