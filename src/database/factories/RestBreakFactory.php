<?php

namespace Database\Factories;

use App\Models\RestBreak;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RestBreak>
 */
class RestBreakFactory extends Factory
{
    protected $model = RestBreak::class;

    public function definition(): array
    {
        $breakStart = Carbon::today()
            ->setTime(rand(12, 15), rand(0, 59));

        $breakEnd = (clone $breakStart)->addMinutes(rand(30, 90));

        $totalBreakTime = floor($breakStart->diffInSeconds($breakEnd) / 60);

        return [
            'attendance_id'     => Attendance::factory(),
            'break_start'       => $breakStart,
            'break_end'         => $breakEnd,
            'total_break_time'  => $totalBreakTime,
            'display_order'     => 1,
            'deleted_at'        => null,
        ];
    }
}

