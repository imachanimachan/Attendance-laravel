<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="{{ asset('css/layout/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout/common.css') }}">
    @yield('css')
</head>

<body class="page">
    <header class="top-header">
        <div class="top-header__inner">
            <div class="top-header__logo">
                <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH" class="top-header__logo-img">
            </div>
            <nav class="top-header__nav">
                <ul class="top-header__nav-list">
                @if (Auth::user()->isAdmin())
                <li><a class="top-header__nav-item" href="/admin/attendances">勤怠一覧</a></li>
                <li><a class="top-header__nav-item" href="/admin/users">スタッフ一覧</a></li>
                <li><a class="top-header__nav-item" href="/admin/requests">申請一覧</a></li>
                @elseif (Route::currentRouteName() === 'attendance.index' &&
                ($attendance->status->name ?? '') === '退勤済')
                <li><a class="top-header__nav-item" href="/attendance/list">今月の勤怠一覧</a></li>
                <li><a class="top-header__nav-item" href="/stamp_correction_request/list">申請一覧</a></li>
                @else
                <li><a class="top-header__nav-item" href="/attendance">勤怠</a></li>
                <li><a class="top-header__nav-item" href="/attendance/list">勤怠一覧</a></li>
                <li><a class="top-header__nav-item" href="/stamp_correction_request/list">申請</a></li>
                @endif
                <li>
                    <form action="/logout" method="post">
                        @csrf
                        <button class="top-header__nav-link">ログアウト</button>
                    </form>
                </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        @yield('content')
    </main>

</body>

</html>