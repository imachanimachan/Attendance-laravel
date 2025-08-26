<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\StatusesTableSeeder;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
	{
		parent::setUp();

		$this->seed(StatusesTableSeeder::class);
	}

    public function test_off_duty_user_sees_off_duty_status_when_logged_in()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('勤務外');
    }

    #[DataProvider('statusProvider')]
    public function test_it_displays_correct_status_on_clock_page($statusId, $expectedText)
    {
        $user = User::factory()->create();

            Attendance::factory()->create([
                'user_id'   => $user->id,
                'status_id' => $statusId,
                'date'      => today(),
                'clock_in'  => now(),
            ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee($expectedText);
    }

    public static function statusProvider()
    {
        return [
            [2, '出勤中'],
            [3, '休憩中'],
            [4, '退勤済'],
        ];
    }
}
