<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Business extends Model
{
    use LogsActivity;

    // Log all attributes, but only when they change
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'business';

    // Optional: customize the description for each event
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Business was {$eventName}";
    }

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
        // All error flag fields
        'name_error',
        'description_error',
        'address_error',
        'village_id_error',
        'sls_id_error',
        'status_bangunan_error',
        'business_category_id_error',
        'phone_error',
        'email_error',
        'owner_name_error',
        'owner_gender_error',
        'owner_age_error',
        'online_status_error',
        'pembinaan_error',
        'catatan_error',
        'user_id_error',
        'final_flag',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('business');
    }
}
