<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use App\Models\AttendanceRevision;
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
            ->get(route('admin.request.list'));
        $response->assertStatus(200);
        $response->assertSee($date->format('Y/m/d'));
        $response->assertSee('電車遅延のため');
        $response->assertSee($user->name);

        $response = $this->actingAs($adminUser)
            ->get(route( 'admin.request.show', ['attendance_correct_request'=> $attendance->id]));
        $response->assertStatus(200);
        $response->assertSee('電車遅延のため');
        $response->assertSee($clockIn->copy()->addHour()->format('H:i'));
        $response->assertSee($clockOut->format('H:i'));
    }

    public function test_it_displays_pending_revision_with_all_details_in_request_list_when_submitted()
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
                'clock_out' => $clockOut->copy()->addHour()->format('H:i'),
                'note'      => '電車遅延のため',
            ]);

        $this->assertDatabaseHas('attendance_revisions', [
            'attendance_id'      => $attendance->id,
            'applied_on'         => $date->toDateString(),
            'original_clock_in'  => $clockIn->format('Y-m-d H:i:s'),
            'original_clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'revised_clock_in'   => $clockIn->copy()->addHour()->format('Y-m-d H:i:s'),
            'revised_clock_out'  => $clockOut->copy()->addHour()->format('Y-m-d H:i:s'),
            'note'               => '電車遅延のため',
            'status'             => 1,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee($user->name);
        $response->assertSee($date->format('Y/m/d'));
        $response->assertSee('電車遅延のため');
        $response->assertSee($date->format('Y/m/d'));
    }

    public function test_approved_revisions_are_displayed_in_request_list_for_admin()
    {
        $user = User::factory()->create();
        $date = now();

        $clockIn = $date->copy()->setTime(9, 0);
        $clockOut = $clockIn->copy()->addHours(8);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 4,
            'date'      => $date->toDateString(),
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $revision = AttendanceRevision::factory()->create([
            'attendance_id'      => $attendance->id,
            'applied_on'         => $date->toDateString(),
            'original_clock_in'  => $clockIn,
            'original_clock_out' => $clockOut,
            'revised_clock_in'   => $clockIn->copy()->addHour(),
            'revised_clock_out'  => $clockOut->copy()->addHour(),
            'note'               => '電車遅延のため',
            'status'             => 2,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee($user->name);
        $response->assertSee($date->format('Y/m/d'));
        $response->assertSee('電車遅延のため');
        $response->assertSee($date->format('Y/m/d'));
    }

    public function test_attendance_detail_is_displayed_correctly_from_request_list()
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
                'clock_out' => $clockOut->copy()->addHour()->format('H:i'),
                'note'      => '電車遅延のため',
            ]);

        $this->assertDatabaseHas('attendance_revisions', [
            'attendance_id'      => $attendance->id,
            'applied_on'         => $date->toDateString(),
            'original_clock_in'  => $clockIn->format('Y-m-d H:i:s'),
            'original_clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'revised_clock_in'   => $clockIn->copy()->addHour()->format('Y-m-d H:i:s'),
            'revised_clock_out'  => $clockOut->copy()->addHour()->format('Y-m-d H:i:s'),
            'note'               => '電車遅延のため',
            'status'             => 1,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSee('詳細');

        $response = $this->actingAs($user)
            ->get(route( 'attendance.show' ,['id' => $attendance->id]));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($date->format('Y年　n月j日'));
        $response->assertSee($clockIn->format('H:i'));
        $response->assertSee($clockOut->format('H:i'));
    }
}
