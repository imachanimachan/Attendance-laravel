<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;
use App\Models\RestBreak;

class AttendanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_attendance_request_appears_in_admin_list_and_detail()
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

        $this->actingAs($user)
            ->post(route('attendance.request', $attendance->id), [
                'clock_in'  => $clockIn->copy()->addHour()->format('H:i'),
                'clock_out' => $clockOut->format('H:i'),
                'note'      => '電車遅延のため',
            ]);

        $adminUser = User::factory()->create(['role' => 2]);

        $response = $this->actingAs($adminUser)
            ->get('/admin/requests');
        $response->assertStatus(200);
        $response->assertSee($date->format('Y/m/d'));
        $response->assertSee('電車遅延のため');
        $response->assertSee($user->name);

        $response = $this->actingAs($adminUser)
            ->get(route('admin.request.show', $attendance->id));
        $response->assertStatus(200);
        $response->assertSee('電車遅延のため');
        $response->assertSee($clockIn->copy()->addHour()->format('H:i'));
        $response->assertSee($clockOut->format('H:i'));
    }
}
