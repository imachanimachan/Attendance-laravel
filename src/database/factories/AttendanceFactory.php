<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
protected $model = Attendance::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-1 month', 'now');
        $clockIn = Carbon::instance($date)->setTime(rand(8, 10), rand(0, 59));
        $clockOut = (clone $clockIn)->addHours(rand(7, 9))->addMinutes(rand(0, 59));
        $totalWorkTime = floor($clockIn->diffInSeconds($clockOut) / 60);

        return [
            'user_id' => User::factory(),
            'status_id' => 4,
            'date' => $clockIn->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'total_work_time' => $totalWorkTime,
        ];
    }
}
