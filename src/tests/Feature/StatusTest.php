<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class StatusTest extends TestCase
{
    use RefreshDatabase;
//これはとりあえず、attendanceclockを参考にしようとコピーしただけ。まだ何もいじってない
    public function test_it_displays_current_datetime_on_clock_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response->assertStatus(200);

        $expectedDate = $now->isoFormat('YYYY年M月D日(ddd)');
        $response->assertSee($expectedDate);

        $expectedTime = $now->format('H:i');
        $response->assertSee($expectedTime);
    }
}
