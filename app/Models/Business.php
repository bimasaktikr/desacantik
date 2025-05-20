<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    //

    // add fillable
    protected $fillable = [
        'name',
        'description',
        'address',
        'phone',
        'email',
        'website',
        'online_status_id',
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    public function certifications()
    {
        return $this->belongsToMany(Certification::class)
                ->withPivot('issue_date')
                ->withTimestamps();
    }
}
