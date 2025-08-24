<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RestBreak extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    use softDeletes;

    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
        'total_break_time',
        'display_order'
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
