<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RestBreak;

class BreaksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $breaks = [
            [
                'id' => 1,
                'attendance_id' => 1,
                'break_start' => '2025-07-12 12:00:00',
                'break_end' => '2025-07-12 13:00:00',
                'total_break_time' => '60',
                'display_order' => 1
            ],

            [
                'id' => 2,
                'attendance_id' => 2,
                'break_start' => '2025-07-11 12:00:00',
                'break_end' => '2025-07-11 14:00:00',
                'total_break_time' => '120',
                'display_order' => 1

            ],

            [
                'id' => 3,
                'attendance_id' => 3,
                'break_start' => '2025-07-13 12:00:00',
                'break_end' => '2025-07-13 13:00:00',
                'total_break_time' => '60',
                'display_order' => 1
            ],

            [
                'id' => 4,
                'attendance_id' => 4,
                'break_start' => '2025-08-12 12:00:00',
                'break_end' => '2025-08-12 13:00:00',
                'total_break_time' => '60',
                'display_order' => 1
            ],

            [
                'id' => 5,
                'attendance_id' => 5,
                'break_start' => '2025-06-12 12:00:00',
                'break_end' => '2025-06-12 13:00:00',
                'total_break_time' => '60',
                'display_order' => 1
            ],

            [
                'id' => 6,
                'attendance_id' => 1,
                'break_start' => '2025-07-12 15:00:00',
                'break_end' => '2025-07-12 15:30:00',
                'total_break_time' => '30',
                'display_order' => 2
            ],

            [
                'id' => 7,
                'attendance_id' => 1,
                'break_start' => '2025-07-12 17:00:00',
                'break_end' => '2025-07-12 17:30:00',
                'total_break_time' => '30',
                'display_order' => 3
            ],

        ];

            foreach ($breaks as $break) {
            RestBreak::create($break);
        }
    }
}
