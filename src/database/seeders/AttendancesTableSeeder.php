<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Status;
use App\Models\User;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = User::first()->id ?? User::factory()->create()->id;
        $statuses = Status::pluck('id', 'name');

        $attendances = [

            [
                'id' => 1,
                'user_id' => $userId,
                'status_id' => $statuses['退勤済'],
                'date' => '2025-07-12',
                'clock_in' => '2025-07-12 09:00:00',
                'clock_out' => '2025-07-12 18:00:00',
                'total_work_time' => '420',
            ],

            [
                'id' => 2,
                'user_id' => $userId,
                'status_id' => $statuses['退勤済'],
                'date' => '2025-07-11',
                'clock_in' => '2025-07-11 09:00:00',
                'clock_out' => '2025-07-11 18:00:00',
                'total_work_time' => '420',
            ],

            [
                'id' => 3,
                'user_id' => $userId,
                'status_id' => $statuses['退勤済'],
                'date' => '2025-07-13',
                'clock_in' => '2025-07-13 09:00:00',
                'clock_out' => '2025-07-13 18:00:00',
                'total_work_time' => '480',

            ],
                        [
                'id' => 4,
                'user_id' => $userId,
                'status_id' => $statuses['退勤済'],
                'date' => '2025-08-12',
                'clock_in' => '2025-08-12 09:00:00',
                'clock_out' => '2025-08-12 18:00:00',
                'total_work_time' => '480',
            ],
            [
                'id' => 5,
                'user_id' => $userId,
                'status_id' => $statuses['退勤済'],
                'date' => '2025-06-12',
                'clock_in' => '2025-06-12 09:00:00',
                'clock_out' => '2025-06-12 18:00:00',
                'total_work_time' => '480',
            ],
            [
                'id' => 6,
                'user_id' => $userId,
                'status_id' => $statuses['退勤済'],
                'date' => '2025-09-05',
                'clock_in' => '2025-09-05 09:00:00',
                'clock_out' => '2025-09-05 18:00:00',
                'total_work_time' => '480',
            ],
            [
                'id' => 7,
                'user_id' => $userId,
                'status_id' => $statuses['退勤済'],
                'date' => '2025-10-05',
                'clock_in' => '2025-10-05 09:00:00',
                'clock_out' => '2025-10-05 18:00:00',
                'total_work_time' => '480',
            ],
        ];

            foreach ($attendances as $attendance) {
            Attendance::create($attendance);
        }
    }
}
