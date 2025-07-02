<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Village extends Model
{
    //

    // add fillable
    protected $fillable = [
        'name',
        'slug',
        'district_id',
        'code',
        'village_code',
        'geojson_path',
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(\App\Models\Assignment::class, 'area');
    }

    public function sls()
    {
        return $this->hasMany(\App\Models\Sls::class);
    }

    public function businesses()
    {
        return $this->hasMany(\App\Models\Business::class);
    }
}
