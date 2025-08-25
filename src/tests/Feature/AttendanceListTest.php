<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;
use App\Models\RestBreak;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_勤怠情報が登録されたユーザーにログインして勤怠一覧ページを開いたら勤怠情報がすべて表示されている()
    {
        $user = User::factory()->create();

        $clockInTime  = now()->setDate(2025, 8, 9)->setTime(9, 0);
        $clockOutTime = $clockInTime->copy()->setTime(18, 0);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 4,
            'date'      => $clockInTime->toDateString(),
            'clock_in'  => $clockInTime,
            'clock_out' => $clockOutTime,
        ]);

        $breakStart = $clockInTime->copy()->setTime(12, 0);
        $breakEnd   = $clockInTime->copy()->setTime(13, 0);

        $break = RestBreak::factory()->create([
            'attendance_id'    => $attendance->id,
            'break_start'      => $breakStart,
            'break_end'        => $breakEnd,
            'total_break_time' => floor($breakStart->diffInSeconds($breakEnd) / 60),
        ]);

        $rawWorkSeconds = $clockInTime->diffInSeconds($clockOutTime);

        $totalBreakSeconds = $attendance->breaks
            ->whereNotNull('break_end')
            ->sum(function ($break) {
                return $break->break_start->diffInSeconds($break->break_end);
            });

        $totalWorkMinutes = floor(($rawWorkSeconds - $totalBreakSeconds) / 60);

        $attendance->update([
            'total_work_time' => $totalWorkMinutes,
        ]);

        $displayDate    = $clockInTime->isoFormat('MM/DD(ddd)');
        $clockInDisplay  = $clockInTime->format('H:i');
        $clockOutDisplay = $clockOutTime->format('H:i');

        $breakDisplay = sprintf('%d:%02d', floor(60 / 60), 60 % 60);
        $workDisplay  = sprintf('%d:%02d', floor($totalWorkMinutes / 60), $totalWorkMinutes % 60);

        $this->actingAs($user)
            ->get(route('attendance.list'))
            ->assertSee($displayDate)
            ->assertSee($clockInDisplay)
            ->assertSee($clockOutDisplay)
            ->assertSee($breakDisplay)
            ->assertSee($workDisplay);
    }

    public function test_ユーザーにログインして勤怠一覧ページを開いたら現在の月が表示されている()
    {
        $user = User::factory()->create();

        $displayYear  = date('Y');
        $displayMonth = str_pad(date('m'), 2, '0', STR_PAD_LEFT);
        $currentMonth = $displayYear . '/' . $displayMonth;

        $this->actingAs($user)
            ->get(route('attendance.list'))
            ->assertSee($currentMonth);
    }

    public function test_ユーザーにログインして勤怠一覧ページを開いて前月を押したら現在の月から見て前月が表示されている()
    {
        $user = User::factory()->create();

        $prevMonth = now()->subMonth();
        $year  = $prevMonth->year;
        $month = str_pad($prevMonth->month, 2, '0', STR_PAD_LEFT);

        $this->actingAs($user)
            ->get(route('attendance.list', ['year' => $year, 'month' => $month]))
            ->assertSee($prevMonth->format('Y/m'));
    }

    public function test_ユーザーにログインして勤怠一覧ページを開いて翌月を押したら現在の月から見て翌月が表示されている()
    {
        $user = User::factory()->create();

        $nextMonth = now()->addMonth();
        $year  = $nextMonth->year;
        $month = str_pad($nextMonth->month, 2, '0', STR_PAD_LEFT);

        $this->actingAs($user)
            ->get(route('attendance.list', ['year' => $year, 'month' => $month]))
            ->assertSee($nextMonth->format('Y/m'));
    }

    public function test_勤怠情報が登録されたユーザーにログインして勤怠一覧ページを開いて詳細ボタンを押したら詳細画面に遷移する()
    {
        $user = User::factory()->create();

        $clockInTime  = now()->setDate(2025, 8, 9)->setTime(9, 0);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'status_id' => 4,
            'clock_in'  => $clockInTime,
            'date'     => $clockInTime->toDateString()
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list'));

        $response->assertSee(route('attendance.show', $attendance->id));

        $response = $this->get(route('attendance.show', $attendance->id));
        $response->assertStatus(200);
        $response->assertSee('2025年　8月9日');
    }
}
