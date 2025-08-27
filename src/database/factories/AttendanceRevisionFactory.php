<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceRevision;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceRevision>
 */
class AttendanceRevisionFactory extends Factory
{
    protected $model = AttendanceRevision::class;

    public function definition(): array
    {
        $date = now();
        $clockIn = \Carbon\Carbon::instance($date)->setTime(9, 0);
        $clockOut = (clone $clockIn)->addHours(8);

        return [
            'attendance_id'      => $attendance = Attendance::factory()->create(),
            'applied_on'         => now()->toDateString(),
            'original_clock_in'  => $attendance->clock_in,
            'original_clock_out' => $attendance->clock_out,
            'revised_clock_in'   => \Carbon\Carbon::parse($attendance->clock_in)->addHour()->format('Y-m-d H:i:s'),
            'revised_clock_out'  => \Carbon\Carbon::parse($attendance->clock_out)->addHour()->format('Y-m-d H:i:s'),
            'note'               => '電車遅延のため',
            'status'             => 1,
        ];
    }
}
