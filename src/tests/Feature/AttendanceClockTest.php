<?php

namespace Tests\Feature;

use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceClockTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_displays_current_datetime_on_clock_page()
    {
        Carbon::setTestNow($now = Carbon::now());

        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/attendance');

        $this->followRedirects($response)
            ->assertSee($now->isoFormat('YYYY年M月D日(ddd)'))
            ->assertSee($now->format('H:i'));
    }
}
