<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceRevision extends Model
{
    use HasFactory;

    protected $fillable = [
    'attendance_id',
    'applied_on',
    'original_clock_in',
    'original_clock_out',
    'revised_clock_in',
    'revised_clock_out',
    'note',
    'status'
    ];

    public function attendance()
{
	return $this->belongsTo(Attendance::class);
}

    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;

    public function getStatusLabelAttribute(): string
    {
        return match($this->status)
        {
            self::STATUS_PENDING => '承認待ち',
            self::STATUS_APPROVED => '承認済み',
            default => '不明'
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function breakRevisions()
    {
        return $this->hasMany(BreakRevision::class);
    }

}
