<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Validator;

class RegisterTest extends TestCase
{
    private function validate(array $data)
    {
        $request = new RegisterRequest();
        return Validator::make($data, $request->rules(), $request->messages());
    }

	public function test_it_returns_error_when_name_is_empty()
	{
		$validator = $this->validate([
			'name' => '',
			'email' => 'test@example.com',
			'password' => 'password123',
			'password_confirmation' => 'password123',
		]);

		$this->assertTrue($validator->fails());
		$this->assertEquals('お名前を入力してください', $validator->errors()->first('name'));
	}

	public function test_it_returns_error_when_email_is_empty()
	{
		$validator = $this->validate([
			'name' => 'テストユーザー',
			'email' => '',
			'password' => 'password123',
			'password_confirmation' => 'password123',
		]);

		$this->assertTrue($validator->fails());
		$this->assertEquals('メールアドレスを入力してください', $validator->errors()->first('email'));
	}

	public function test_it_returns_error_when_password_is_empty()
	{
		$validator = $this->validate([
			'name' => 'テストユーザー',
			'email' => 'test@example.com',
			'password' => '',
			'password_confirmation' => '',
		]);

		$this->assertTrue($validator->fails());
		$this->assertEquals('パスワードを入力してください', $validator->errors()->first('password'));
	}

    public function test_it_returns_error_when_password_is_too_short()
	{
		$validator = $this->validate([
			'name' => 'テストユーザー',
			'email' => 'test@example.com',
			'password' => 'pass123',
			'password_confirmation' => 'pass123',
		]);

		$this->assertTrue($validator->fails());
		$this->assertEquals('パスワードは8文字以上で入力してください', $validator->errors()->first('password'));
	}

	public function test_it_returns_error_when_password_confirmation_does_not_match()
	{
		$validator = $this->validate([
			'name' => 'テストユーザー',
			'email' => 'test@example.com',
			'password' => 'password123',
			'password_confirmation' => 'password456',
		]);

		$this->assertTrue($validator->fails());
		$this->assertEquals('パスワードと一致しません', $validator->errors()->first('password'));
	}
}
