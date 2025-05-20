<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function assignments()
    {
        return $this->morphMany(Assignment::class, 'assignable');
    }
}
