<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Basemap extends Model
{
    protected $table = 'base_maps';


    // add fillable
    protected $fillable = [
        'name', 'file_path', 'period', 'regency_name', 'source', 'user_id',
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
