<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_today_attendance_for_all_users_is_displayed_correctly_to_admin()
    {
        $date = now();
        $adminUser = User::factory()->create(['role' => 2]);
        $users = User::factory()->count(3)->create();

        $attendances = $users->map(function($user) use ($date) {
            return Attendance::factory()->create([
                'user_id'   => $user->id,
                'status_id' => 4,
                'date'      => $date->toDateString(),
                'clock_in'  => $date->copy()->setTime(8, 0),
                'clock_out' => $date->copy()->setTime(17, 0),
            ]);
        });

        $response = $this->actingAs($adminUser)
            ->get('/admin/attendance/list');
        $response->assertStatus(200);

        foreach ($attendances as $attendance) {
            $response->assertSee($attendance->user->name);
            $response->assertSee($attendance->clock_in->format('H:i'));
            $response->assertSee($attendance->clock_out->format('H:i'));

            $totalHours   = floor($attendance->total_work_time / 60);
            $totalMinutes = str_pad($attendance->total_work_time % 60, 2, '0', STR_PAD_LEFT);
            $response->assertSee("{$totalHours}:{$totalMinutes}");
        }
    }

    public function test_attendance_list_page_shows_today_date()
    {
        $adminUser = User::factory()->create(['role' => 2]);
        $now =now();

        $response = $this->actingAs($adminUser)
            ->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($now->format('Y/m/d'));
    }

    public function test_previous_day_attendance_is_displayed_correctly_to_admin()
    {
        $adminUser = User::factory()->create(['role' => 2]);
        $today = now();
        $yesterday = $today->copy()->subDay();

        $users = User::factory()->count(3)->create();

        $yesterdayAttendances = $users->map(function($user) use ($yesterday) {
            return Attendance::factory()->create([
                'user_id'   => $user->id,
                'status_id' => 4,
                'date'      => $yesterday->toDateString(),
                'clock_in'  => $yesterday->copy()->setTime(8, 0),
                'clock_out' => $yesterday->copy()->setTime(17, 0),
            ]);
        });

        $response = $this->actingAs($adminUser)
            ->get('/admin/attendance/list?year=' . $yesterday->year .
                '&month=' . $yesterday->month .
                '&day=' . $yesterday->day);

        $response->assertStatus(200);

        foreach ($yesterdayAttendances as $attendance) {
            $response->assertSee($attendance->user->name);
            $response->assertSee($attendance->clock_in->format('H:i'));
            $response->assertSee($attendance->clock_out->format('H:i'));

            $totalHours   = floor($attendance->total_work_time / 60);
            $totalMinutes = str_pad($attendance->total_work_time % 60, 2, '0', STR_PAD_LEFT);
            $response->assertSee("{$totalHours}:{$totalMinutes}");
        }
    }

    public function test_next_day_attendance_is_displayed_correctly_to_admin()
    {
        $adminUser = User::factory()->create(['role' => 2]);
        $today = now();
        $tomorrow = $today->copy()->addDay();

        $users = User::factory()->count(3)->create();

        $tomorrowAttendances = $users->map(function($user) use ($tomorrow) {
            return Attendance::factory()->create([
                'user_id'   => $user->id,
                'status_id' => 4,
                'date'      => $tomorrow->toDateString(),
                'clock_in'  => $tomorrow->copy()->setTime(8, 0),
                'clock_out' => $tomorrow->copy()->setTime(17, 0),
            ]);
        });

        $response = $this->actingAs($adminUser)
            ->get('/admin/attendance/list?year=' . $tomorrow->year .
                '&month=' . $tomorrow->month .
                '&day=' . $tomorrow->day);

        $response->assertStatus(200);

        foreach ($tomorrowAttendances as $attendance) {
            $response->assertSee($attendance->user->name);
            $response->assertSee($attendance->clock_in->format('H:i'));
            $response->assertSee($attendance->clock_out->format('H:i'));

            $totalHours   = floor($attendance->total_work_time / 60);
            $totalMinutes = str_pad($attendance->total_work_time % 60, 2, '0', STR_PAD_LEFT);
            $response->assertSee("{$totalHours}:{$totalMinutes}");
        }
    }

}
