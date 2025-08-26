<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}
    public function test_it_displays_clock_out_button_and_marks_as_clocked_out_after_pressing()
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
            ->assertSee('退勤');

        $this->actingAs($user)
            ->followingRedirects()
            ->patch('/attendance')
            ->assertSee('退勤済');
    }

    public function test_clock_in_and_clock_out_times_are_displayed_correctly_on_attendance_list_after_login()
    {
        $user = User::factory()->create();

        $clockInTime = now()->setTime(9, 0);
        Carbon::setTestNow($clockInTime);

        $this->actingAs($user)->post('/attendance');

        $clockOutTime = now()->setTime(18, 0);
        Carbon::setTestNow($clockOutTime);

        $this->actingAs($user)
            ->followingRedirects()
            ->patch('/attendance');

        $this->actingAs($user)
            ->get(route('attendance.list'))
            ->assertSee($clockInTime->format('H:i'))
            ->assertSee($clockOutTime->format('H:i'));
    }
}
