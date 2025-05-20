<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        return $this->belongsTo(BaseMap::class);
    }
}
