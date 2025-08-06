<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakRevision extends Model
{
        protected $fillable = [
        'break_id',
        'attendance_revision_id',
        'original_break_start',
        'original_break_end',
        'revised_break_start',
        'revised_break_end',
    ];

    public function break()
    {
        return $this->belongsTo(RestBreak::class);
    }


}
