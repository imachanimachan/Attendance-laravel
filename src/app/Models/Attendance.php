<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'status_id',
        'date',
        'clock_in',
        'clock_out',
        'total_work_time',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function breaks()
    {
        return $this->hasMany(RestBreak::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
