<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sls extends Model
{
    //

    // add fillable
    protected $fillable = [
        'name',
        'slug',
        'village_id',
        'code',
        'sls_code',
        'geojson_path',
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function baseMap()
    {
        return $this->belongsTo(Basemap::class);
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(\App\Models\Assignment::class, 'area');
    }

    public function businesses()
    {
        return $this->hasMany(\App\Models\Business::class);
    }
}
