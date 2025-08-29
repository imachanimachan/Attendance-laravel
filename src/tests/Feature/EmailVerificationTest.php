<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_email_is_sent_after_registration()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'test User',
            'email' => 'Test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/email/verify');

        $user =User::where('email', 'test@example.com')->first();

        Notification::assertSentTo(
            [$user],
            VerifyEmail::class
        );
    }

    public function test_verify_email_button_links_to_mailhog()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');
        $response->assertStatus(200);
        $response->assertSee('認証はこちらから');

        $response->assertSee('href="http://localhost:8025/"', false);
    }

    public function test_email_verification_redirects_to_attendance_screen()
	{
		$user = User::factory()->unverified()->create();

        $user->markEmailAsVerified();

        $attendanceResponse = $this->actingAs($user)->get('/attendance');
        $attendanceResponse->assertStatus(200);
        $attendanceResponse->assertSee('出勤');
	}
}
