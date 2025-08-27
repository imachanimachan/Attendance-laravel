<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;
use App\Models\RestBreak;

class AdminRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_error_is_shown_when_clock_in_is_after_clock_out()
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

        $response = $this->post(
                route( 'admin.request' ,['id' => $attendance->id]),
                [
                    'clock_in'  => $clockOut->copy()->addHours(2)->format('H:i'),
                    'clock_out' => $clockOut->format('H:i'),
                ]
            );

            $response->assertSessionHasErrors([
                'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
            ]);
    }

    public function test_error_is_shown_when_break_start_is_after_clock_out()
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

        $break = RestBreak::factory()->create([
            'attendance_id'    => $attendance->id,
        ]);

        $adminUser = User::factory()->create(['role' => 2]);

        $response = $this->actingAs($adminUser)
            ->get(route( 'admin.show' ,['id' => $attendance->id]));
        $response->assertStatus(200);

        $order = 1;
        $response = $this->post(
            route('attendance.request', $attendance->id),
            [
                'clock_in'  => $clockIn->format('H:i'),
                'clock_out' => $clockOut->format('H:i'),
                'breaks' => [
                    $order => [
                        'break_start' => $clockOut->copy()->addHours()->format('H:i'),
                        'break_end'   => $break->break_end->format('H:i'),
                    ],
                ],
            ]
        );

        $response->assertSessionHasErrors([
            "breaks.$order.break_start" => "休憩時間が不適切な値です",
        ]);
    }

    public function test_error_is_shown_when_break_end_is_after_clock_out()
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

        $break = RestBreak::factory()->create([
            'attendance_id'    => $attendance->id,
        ]);

        $adminUser = User::factory()->create(['role' => 2]);

        $response = $this->actingAs($adminUser)
            ->get(route( 'admin.show' ,['id' => $attendance->id]));
        $response->assertStatus(200);

        $order = 1;
        $response = $this->post(
            route('attendance.request', $attendance->id),
            [
                'clock_in'  => $clockIn->format('H:i'),
                'clock_out' => $clockOut->format('H:i'),
                'breaks' => [
                    $order => [
                        'break_start' => $break->break_start->format('H:i'),
                        'break_end'   => $clockOut->copy()->addHours()->format('H:i'),
                    ],
                ],
            ]
        );

        $response->assertSessionHasErrors([
            "breaks.$order.break_end" => "休憩時間もしくは退勤時間が不適切な値です",
        ]);
    }

    public function test_備考欄が未入力の場合エラーメッセージ()
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

        $break = RestBreak::factory()->create([
            'attendance_id'    => $attendance->id,
        ]);

        $adminUser = User::factory()->create(['role' => 2]);

        $response = $this->actingAs($adminUser)
            ->get(route( 'admin.show' ,['id' => $attendance->id]));
        $response->assertStatus(200);

        $response = $this->post(
            route('attendance.request', $attendance->id),
            ['note' => '']
        );

        $response->assertSessionHasErrors([
            "note" => "備考を記入してください",
        ]);
    }
}
