<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\RestBreak;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;
use App\Models\AttendanceRevision;

class AdminRequestListTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_pending_requests_from_all_users_are_displayed_on_admin_requests_page()
    {
        $adminUser = User::factory()->create(['role' => 2]);
        $date = now();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $clockIn = $date->copy()->setTime(9, 0);
            $clockOut = $clockIn->copy()->addHours(8);

            $attendance = Attendance::factory()->create([
                'user_id'   => $user->id,
                'status_id' => 4,
                'date'      => $date->toDateString(),
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
            ]);

            AttendanceRevision::factory()->create([
                'attendance_id'      => $attendance->id,
                'applied_on'         => $date->toDateString(),
                'original_clock_in'  => $clockIn,
                'original_clock_out' => $clockOut,
                'revised_clock_in'   => $clockIn->copy()->addHour(),
                'revised_clock_out'  => $clockOut->copy()->addHour(),
                'note'               => '電車遅延のため',
                'status'             => 1,
            ]);
        }

        $response = $this->actingAs($adminUser)
            ->get(route('admin.request.list', ['tab' =>'pending']));

        $response->assertStatus(200);
        $response->assertSee('承認待ち');

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($date->format('Y/m/d'));
            $response->assertSee('電車遅延のため');
        }
    }

    public function test_approved_requests_from_all_users_are_displayed_on_admin_requests_page()
    {
        $adminUser = User::factory()->create(['role' => 2]);
        $date = now();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $clockIn = $date->copy()->setTime(9, 0);
            $clockOut = $clockIn->copy()->addHours(8);

            $attendance = Attendance::factory()->create([
                'user_id'   => $user->id,
                'status_id' => 4,
                'date'      => $date->toDateString(),
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
            ]);

            AttendanceRevision::factory()->create([
                'attendance_id'      => $attendance->id,
                'applied_on'         => $date->toDateString(),
                'original_clock_in'  => $clockIn,
                'original_clock_out' => $clockOut,
                'revised_clock_in'   => $clockIn->copy()->addHour(),
                'revised_clock_out'  => $clockOut->copy()->addHour(),
                'note'               => '電車遅延のため',
                'status'             => 2,
            ]);
        }

        $response = $this->actingAs($adminUser)
            ->get(route('admin.request.list', ['tab' =>'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済み');

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($date->format('Y/m/d'));
            $response->assertSee('電車遅延のため');
        }
    }

    public function test_attendance_revision_detail_is_displayed_correctly_to_admin()
    {
        $adminUser = User::factory()->create(['role' => 2]);
        $date = now();
        $user = User::factory()->create();

        $clockIn = $date->copy()->setTime(9, 0);
        $clockOut = $clockIn->copy()->addHours(8);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 4,
            'date'      => $date->toDateString(),
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $attendanceRevision = AttendanceRevision::factory()->create([
            'attendance_id'      => $attendance->id,
            'applied_on'         => $date->toDateString(),
            'original_clock_in'  => $clockIn,
            'original_clock_out' => $clockOut,
            'revised_clock_in'   => $clockIn->copy()->addHour(),
            'revised_clock_out'  => $clockOut->copy()->addHour(),
            'note'               => '電車遅延のため',
            'status'             => 1,
        ]);

        $response = $this->actingAs($adminUser)
            ->get(route('admin.request.show' ,['attendance_correct_request' => $attendance->id]));

        $response->assertStatus(200);

        $response->assertSee($user->name);
        $response->assertSee(\Carbon\Carbon::parse($attendance->date)->format('Y年　n月j日'));
        $response->assertSee($attendanceRevision->revised_clock_in->format('H:i'));
        $response->assertSee($attendanceRevision->revised_clock_out->format('H:i'));
        $response->assertSee('電車遅延のため');
    }

    public function test_attendance_revision_is_approved_and_updates_attendance_correctly()
    {
        $adminUser = User::factory()->create(['role' => 2]);
        $user = User::factory()->create();

        $date = now();
        $clockIn = $date->copy()->setTime(9, 0);
        $clockOut = $date->copy()->setTime(18, 0);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 4,
            'date'      => $date->toDateString(),
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $attendanceRevision = AttendanceRevision::factory()->create([
            'attendance_id'      => $attendance->id,
            'applied_on'         => $date->toDateString(),
            'original_clock_in'  => $clockIn,
            'original_clock_out' => $clockOut,
            'revised_clock_in'   => $clockIn->copy()->addHour(),
            'revised_clock_out'  => $clockOut->copy()->addHour(),
            'note'               => '電車遅延のため',
            'status'             => 1,
        ]);

        $response = $this->actingAs($adminUser)
            ->followingRedirects()
            ->patch(route('admin.approved', ['attendance_correct_request' => $attendance->id]), [
                'clockIn' => $clockIn->copy()->addHour()->format('H:i'),
                'clockOut' => $clockOut->copy()->addHour()->format('H:i'),
            ]);

        $this->assertDatabaseHas('attendance_revisions', [
            'id'     => $attendanceRevision->id,
            'status' => 2,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id'       => $attendance->id,
            'clock_in' => $clockIn->copy()->addHour()->format('Y-m-d H:i:s'),
            'clock_out'=> $clockOut->copy()->addHour()->format('Y-m-d H:i:s'),
        ]);
    }
}
