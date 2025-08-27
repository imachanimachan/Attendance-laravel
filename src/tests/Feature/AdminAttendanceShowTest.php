<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;

class AdminAttendanceShowTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_attendance_detail_displays_correct_values_to_admin()
    {
        $user = User::factory()->create();
        $date = now();

        $clockIn = $date->copy()->setTime(8, 0);
        $clockOut = $clockIn->copy()->addHours(8);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 4,
            'date'      => $date->toDateString(),
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $adminUser = User::factory()->create(['role' => 2]);

        $response = $this->actingAs($adminUser)
            ->get(route( 'admin.show' ,['id' => $attendance->id]));
        $response->assertStatus(200);

        $response->assertSee($user->name);
        $response->assertSee($date->format('Y年　n月j日'));
        $response->assertSee($clockIn->format('H:i'));
        $response->assertSee($clockOut->format('H:i'));
    }
}
