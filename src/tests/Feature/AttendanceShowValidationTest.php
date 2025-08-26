<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;
use App\Models\RestBreak;

class AttendanceShowValidationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_clock_in_after_clock_out_shows_error()
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

        $this->actingAs($user);

        $response = $this->post(
                route('attendance.request', $attendance->id),
                [
                    'clock_in'  => $clockOut->copy()->addHours(2)->format('H:i'),
                    'clock_out' => $clockOut->format('H:i'),
                ]
            );

            $response->assertSessionHasErrors([
                'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
            ]);
    }

    public function test_break_start_after_clock_out_shows_error()
    {
        $user = User::factory()->create();
        $date = now();

        $clockIn = $date->copy()->setTime(8, 0, 0);
        $clockOut = $clockIn->copy()->addHours(8);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 4,
            'date' => $date->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $break = RestBreak::factory()->create([
            'attendance_id'    => $attendance->id,
        ]);

        $this->actingAs($user);

        $order = 1;

        $response = $this->post(
            route('attendance.request', $attendance->id),
            [
                'clock_in'  => $clockIn->format('H:i'),
                'clock_out' => $clockOut->format('H:i'),
                'breaks' => [
                    $order => [
                        'break_start' => $clockOut->copy()->addHours(1)->format('H:i'),
                        'break_end'   => $break->break_end->format('H:i'),
                    ],
                ],
            ]
        );

        $response->assertSessionHasErrors([
            "breaks.$order.break_start" => "休憩時間が不適切な値です",
        ]);
    }
    public function test_note_field_required_shows_error()
    {
        $user = User::factory()->create();
        $date = now();

        $clockIn = $date->copy()->setTime(8, 0, 0);
        $clockOut = $clockIn->copy()->addHours(8);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 4,
            'date' => $date->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $this->actingAs($user);

        $response = $this->post(
            route('attendance.request', $attendance->id),
            ['note' => '']
        );

        $response->assertSessionHasErrors([
            "note" => "備考を記入してください",
        ]);
    }
}
