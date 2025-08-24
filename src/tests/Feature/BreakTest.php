<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use App\Models\RestBreak;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_it_displays_break_button_and_shows_breaking_status_when_pressed()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 2,
            'date'      => today(),
            'clock_in'  => now(),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('休憩入');

        $this->actingAs($user)
            ->followingRedirects()
            ->post('/break')
            ->assertSee('休憩中');
    }

    public function test_user_can_return_from_break_and_break_button_is_displayed_again()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 2,
            'date'      => today(),
            'clock_in'  => now(),
        ]);

        $this->actingAs($user)
            ->post('/break');

        $this->actingAs($user)
            ->patch('/break');

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->get('/attendance');

        $response->assertSee('休憩入');
    }

    public function test_user_can_return_from_break_and_shows_working_status()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 2,
            'date'      => today(),
            'clock_in'  => now(),
        ]);

        $this->actingAs($user)
            ->post('/break');

        $this->actingAs($user)
            ->patch('/break');

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->get('/attendance');

        $response->assertSee('出勤中');
    }

    public function test_user_can_start_break_again_after_return_and_sees_return_break_button()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 2,
            'date'      => today(),
            'clock_in'  => now(),
        ]);

        $this->actingAs($user)
            ->post('/break');

        $this->actingAs($user)
            ->patch('/break');

        $this->actingAs($user)
            ->post('/break');

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->get('/attendance');

        $response->assertSee('休憩戻');
    }

    public function test_user_can_start_and_end_break_and_see_correct_times_in_attendance_list()
    {
        $user = User::factory()->create();

        $clockInTime = now()->setTime(9, 0);
        Carbon::setTestNow($clockInTime);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 2,
            'date'      => today(),
            'clock_in'  => $clockInTime,
        ]);

        $breakStart = $clockInTime->copy()->addHours(4);
        Carbon::setTestNow($breakStart);
        $this->actingAs($user)->post('/break');

        $breakEnd = $clockInTime->copy()->addHours(5);
        Carbon::setTestNow($breakEnd);
        $this->actingAs($user)->patch('/break');

        $this->actingAs($user)
            ->get(route('attendance.list'))
            ->assertSee('1:00');
    }
}
