<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}
    public function test_clock_in_button_is_displayed_and_status_changes_to_working_after_post()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 1,
            'date'      => today(),
            'clock_in'  => now()->subMinutes(1),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('出勤');

        $this->actingAs($user)
            ->followingRedirects()
            ->post('/attendance')
            ->assertSee('勤務中');
    }

    public function test_clock_in_button_is_not_displayed_for_user_already_clocked_out()
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id'    => $user->id,
            'status_id'  => 4,
            'date'       => today(),
            'clock_in'   => now()->subHours(8),
            'clock_out'  => now()->subMinutes(5),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('退勤済')
            ->assertDontSee('出勤');
    }

    public function test_clock_in_time_is_displayed_correctly_after_clocking_in_from_off_duty_status()
    {
        $user = User::factory()->create();

        Carbon::setTestNow($fixedNow = now());

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 1,
            'date'      => today(),
            'clock_in'  => $fixedNow->subMinutes(1),
        ]);

        $this->actingAs($user)->post('/attendance');

        $this->actingAs($user)
            ->get(route('attendance.list'))
            ->assertSee($fixedNow->format('H:i'));
    }
}



