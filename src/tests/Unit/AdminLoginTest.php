<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AdminLoginTest extends TestCase
{
	use RefreshDatabase;

    private function validate(array $data)
	{
		$request = new LoginRequest();
		return Validator::make($data, $request->rules(), $request->messages());
	}

	public function test_it_returns_error_when_email_is_empty()
	{
		User::factory()->create([
			'email' => 'test@example.com',
			'password' => bcrypt('password123'),
			'role' => 2
		]);

		$validator = $this->validate([
			'email' => '',
			'password' => 'password123',
		]);

		$this->assertTrue($validator->fails());
		$this->assertEquals('メールアドレスを入力してください', $validator->errors()->first('email'));
	}

	public function test_it_returns_error_when_password_is_empty()
	{
		User::factory()->create([
			'email' => 'test@example.com',
			'password' => bcrypt('password123'),
			'role' => 2
		]);

		$validator = $this->validate([
			'email' => 'test@example.com',
			'password' => '',
		]);

		$this->assertTrue($validator->fails());
		$this->assertEquals('パスワードを入力してください', $validator->errors()->first('password'));
	}

}
