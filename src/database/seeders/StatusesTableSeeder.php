<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::create(['id' => 1, 'name' => '勤務外']);
        Status::create(['id' => 2, 'name' => '出勤中']);
        Status::create(['id' => 3, 'name' => '休憩中']);
        Status::create(['id' => 4, 'name' => '退勤済']);
    }
}
