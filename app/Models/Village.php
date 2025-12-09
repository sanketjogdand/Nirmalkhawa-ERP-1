<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    protected $fillable = ['name', 'taluka_id'];

    public function taluka()
    {
        return $this->belongsTo(Taluka::class);
    }
}