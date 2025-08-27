<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\RestBreak;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;

class AdminUserListTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_all_users_names_and_emails_are_displayed_on_staff_list()
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
            ->get('/admin/users');
        $response->assertStatus(200);

        foreach ($attendances as $attendance) {
            $response->assertSee($attendance->user->name);
            $response->assertSee($attendance->email);
        }
    }

    public function test_selected_user_attendance_is_displayed_correctly_to_admin()
    {
        $adminUser = User::factory()->create(['role' => 2]);
        $targetUser = User::factory()->create();

        $date = now();
        $clockIn = $date->copy()->setTime(8, 0);
        $clockOut = $date->copy()->setTime(17, 0);

        $attendance = Attendance::factory()->create([
            'user_id'   => $targetUser->id,
            'status_id' => 4,
            'date'      => $date->toDateString(),
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $breakStart = $clockIn->copy()->setTime(12, 0);
        $breakEnd   = $clockIn->copy()->setTime(13, 0);

        $break = RestBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start'      => $breakStart,
            'break_end'        => $breakEnd,
            'total_break_time' => 60,
        ]);

        $attendance->update([
            'total_work_time' => 480,
        ]);

        $response = $this->actingAs($adminUser)
            ->get(route('admin.users.attendances', ['user' => $targetUser->id]));

        $response->assertStatus(200);
        $response->assertSee($targetUser->name);
        $response->assertSee($attendance->clock_in->format('H:i'));
        $response->assertSee($attendance->clock_out->format('H:i'));
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    public function test_previous_month_attendance_is_displayed_correctly_to_admin()
    {
        $adminUser = User::factory()->create(['role' => 2]);

        $today = now();

        $previousMonthDate = $today->copy()->subMonth();
        $year  = $previousMonthDate->year;
        $month = $previousMonthDate->month;

        $targetUser = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $targetUser->id,
            'status_id' => 4,
            'date'      => $previousMonthDate->toDateString(),
            'clock_in'  => $previousMonthDate->copy()->setTime(8, 0),
            'clock_out' => $previousMonthDate->copy()->setTime(17, 0),
            'total_work_time'=> 540,
        ]);

        $response = $this->actingAs($adminUser)
            ->get(route('admin.users.attendances', ['user' => $targetUser->id, 'year' => $year, 'month' => $month]));
        $response->assertStatus(200);

        $response->assertSee($targetUser->name);
        $response->assertSee($attendance->clock_in->format('H:i'));
        $response->assertSee($attendance->clock_out->format('H:i'));

        $totalHours   = floor($attendance->total_work_time / 60);
        $totalMinutes = $attendance->total_work_time % 60;
        $response->assertSee(sprintf("%d:%02d", $totalHours, $totalMinutes));
    }

    public function test_next_month_attendance_is_displayed_correctly_to_admin()
    {
        $adminUser = User::factory()->create(['role' => 2]);

        $today = now();

        $nextMonthDate = $today->copy()->addMonth();
        $year  = $nextMonthDate->year;
        $month = $nextMonthDate->month;

        $targetUser = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $targetUser->id,
            'status_id' => 4,
            'date'      => $nextMonthDate->toDateString(),
            'clock_in'  => $nextMonthDate->copy()->setTime(8, 0),
            'clock_out' => $nextMonthDate->copy()->setTime(17, 0),
            'total_work_time'=> 540,
        ]);

        $response = $this->actingAs($adminUser)
            ->get(route('admin.users.attendances', ['user' => $targetUser->id, 'year' => $year, 'month' => $month]));
        $response->assertStatus(200);

        $response->assertSee($targetUser->name);
        $response->assertSee($attendance->clock_in->format('H:i'));
        $response->assertSee($attendance->clock_out->format('H:i'));

        $totalHours   = floor($attendance->total_work_time / 60);
        $totalMinutes = $attendance->total_work_time % 60;
        $response->assertSee(sprintf("%d:%02d", $totalHours, $totalMinutes));
    }

    public function test_selected_attendance_detail_page_is_displayed()
    {
        $adminUser = User::factory()->create(['role' => 2]);
        $today = now();
        $targetUser = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $targetUser->id,
            'status_id' => 4,
            'date'      => $today->toDateString(),
            'clock_in'  => $today->copy()->setTime(8, 0),
            'clock_out' => $today->copy()->setTime(17, 0),
            'total_work_time'=> 540,
        ]);

        $response = $this->actingAs($adminUser)
            ->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        $response->assertSee($targetUser->name);
        $response->assertSee(\Carbon\Carbon::parse($attendance->date)->format('Y年　n月j日'));
        $response->assertSee($attendance->clock_in->format('H:i'));
        $response->assertSee($attendance->clock_out->format('H:i'));
    }
}
