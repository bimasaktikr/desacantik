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
        'sls_id',
        'latitude',
        'longitude',
        'status_bangunan',
        'business_category_id',
        'phone',
        'email',
        'owner_name',
        'owner_gender',
        'owner_age',
        'online_status',
        'pembinaan',
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

    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    public function sls()
    {
        return $this->belongsTo(Sls::class);
    }
}
