<!DOCTYPE html>
<html lang="ja">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>COACHTECH</title>
        <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
        <link rel="stylesheet" href="{{ asset('css/auth/register.css')}}">
    </head>

    <body class="page">
        <header class="top-header">
            <div class="top-header__inner">
                <div class="top-header__logo">
                    <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH" class="top-header__logo-img">
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="register">
                <h2 class="register__title">会員登録</h2>
                <form class="register__form" method="POST" action="/register">
                    @csrf
                    <label class="register__label">ユーザー名
                        <input type="text" name="name" class="register__input" value="{{ old('name') }}">
                        @error('name')
                        <p class="register__error">{{ $message }}</p>
                        @enderror
                    </label>
                    <label class="register__label">メールアドレス
                        <input type="email" name="email" class="register__input" value="{{ old('email') }}">
                        @error('email')
                        <p class="register__error">{{ $message }}</p>
                        @enderror
                    </label>
                    <label class="register__label">パスワード
                        <input type="password" name="password" class="register__input" value="{{ old('password') }}">
                        @error('password')
                        <p class="register__error">{{ $message }}</p>
                        @enderror
                    </label>
                    <label class="register__label">確認用パスワード
                        <input type="password" name="password_confirmation" class="register__input" value="{{ old('password_confirmation') }}">
                        @error('password_confirmation')
                        <p class="register__error">{{ $message }}</p>
                        @enderror
                    </label>
                    <button class="register__button" type="submit">登録する</button>
                </form>
                <a class="register__link" href="/login">ログインはこちら</a>
            </div>
        </main>
    </body>
</html>