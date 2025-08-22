<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminFutureLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_error_when_login_with_wrong_email()
    {
        $user = User::factory()->create([
            'email' => 'correct@example.com',
            'password' => bcrypt('password123'),
            'role' => 2
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません。',
        ]);
    }
}
