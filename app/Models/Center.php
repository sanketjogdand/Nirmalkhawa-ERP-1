<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Center extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'state_id',
        'district_id',
        'taluka_id',
        'village_id',
        'contact_person',
        'mobile',
        'account_name',
        'account_number',
        'ifsc',
        'branch',
        'status',
    ];

    public function state() { return $this->belongsTo(State::class); }
    public function district() { return $this->belongsTo(District::class); }
    public function taluka() { return $this->belongsTo(Taluka::class); }
    public function village() { return $this->belongsTo(Village::class); }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}
