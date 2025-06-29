<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    //

    // add fillable
    protected $fillable = [
        'point_id',
        'name',
        'description',
        'address',
        'village_id',
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
        'pertokoan',
        'catatan_lantaibloksektor',
        'catatan',
        'user_id',
        'remarks',
        'time',
        'geometry',
        'gis_x',
        'gis_y',
        'elevation',
        'ortho_height',
        'instrument_ht',
        'fix_id',
        'speed',
        'bearing',
        'horizontal_accuracy',
        'vertical_accuracy',
        'pdop',
        'hdop',
        'vdop',
        'satellites_in_view',
        'satellites_in_use',
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

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
