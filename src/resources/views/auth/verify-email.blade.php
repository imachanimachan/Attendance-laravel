<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth/verify-email.css')}}">
</head>

<body class="page">
    <header class="top-header">
        <div class="top-header__inner">
            <div class="top-header__logo">
                <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH" class="top-header__logo-img">
            </div>
        </div>
    </header>
    <main class="verify-email">
        <div class="verify-email__content">
            <p class="verify-email__text">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>
            @if (session('message'))
            <p class="verify-email__message">{{ session('message') }}</p>
            @endif

            <div class="verify-email__link-wrapper">
                <a href="http://localhost:8025/" class="verify-email__link-button">認証はこちらから</a>
            </div>

            <form method="POST" action="{{ route('verification.send') }}" class="verify-email__form">
                @csrf
                <button type="submit" class="verify-email__resend-button">認証メールを再送する</button>
            </form>
        </div>
    </main>
</body>
</html>