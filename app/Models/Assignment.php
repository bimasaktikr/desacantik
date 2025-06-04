<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Assignment extends Model
{
    //

    // add fillable
    protected $fillable = [
        'user_id',
        'area_type',
        'area_id'
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignmentUploads()
    {
        return $this->hasMany(AssignmentUpload::class);
    }
}
