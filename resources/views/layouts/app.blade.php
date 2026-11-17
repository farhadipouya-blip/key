<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'داشبورد') | کلید — سامانه جامع آموزشی</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/key.css') }}">
    @if (setting('fonts.active_family'))
        <style>:root { --key-font-family: '{{ setting('fonts.active_family') }}'; }</style>
    @endif
    @stack('styles')
</head>
<body>
@php
    $menu = config('menu', []);
    $user = auth()->user();
    $unread = $user ? $user->unreadNotifications()->count() : 0;
@endphp

{{-- سایدبار --}}
<aside class="key-sidebar" id="keySidebar">
    <div class="brand">
        <div class="logo">K</div>
        <div class="name">
            کلید
            <small>سامانه جامع آموزشی</small>
        </div>
    </div>
    <nav class="mt-2 pb-4">
        @foreach ($menu as $section)
            @php
                $visible = collect($section['items'])->filter(function ($item) use ($user) {
                    if (isset($item['permission']) && $item['permission']) {
                        return $user->canAccess($item['permission']);
                    }
                    return true;
                });
            @endphp
            @if ($visible->isNotEmpty())
                <div class="nav-section">{{ $section['section'] }}</div>
                @foreach ($visible as $item)
                    <a href="{{ route_if($item['route']) }}"
                       class="nav-link {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endif
        @endforeach
        <div class="px-4 pt-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link w-100 border-0 bg-transparent text-start text-danger">
                    <i class="bi bi-box-arrow-in-left"></i>
                    <span>خروج از حساب</span>
                </button>
            </form>
        </div>
    </nav>
</aside>


{{-- بدنه اصلی --}}
<div class="key-wrapper">
    <header class="key-topbar d-flex align-items-center px-3 px-lg-4 gap-3">
        <button class="btn btn-outline-secondary btn-sm key-menu-toggle" type="button">
            <i class="bi bi-list"></i>
        </button>

        <div class="flex-grow-1">
            <h6 class="mb-0 fw-bold">@yield('page_title', 'داشبورد')</h6>
        </div>

        <button class="btn btn-link text-decoration-none theme-toggle text-secondary fs-5" type="button" title="حالت روشن/تاریک">
            <i class="bi bi-moon-stars"></i>
        </button>

        @can('tickets.view')
        <div class="dropdown">
            <a class="btn btn-link text-decoration-none position-relative text-secondary fs-5" data-bs-toggle="dropdown"
               href="#" id="notifBell" title="اعلان‌ها">
                <i class="bi bi-bell"></i>
                @if ($unread > 0)
                    <span class="position-absolute top-0 start-0 badge rounded-pill bg-danger" style="font-size:.6rem">{{ $unread }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-end shadow border p-0" style="min-width: 320px">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <strong class="small">اعلان‌ها</strong>
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button class="btn btn-sm btn-link p-0 small text-decoration-none">خواندن همه</button>
                    </form>
                </div>
                <div style="max-height: 320px; overflow-y: auto">
                    @forelse ($user->notifications()->take(8)->get() as $n)
                        <div class="notif-item p-3 {{ $n->read_at ? '' : 'unread' }}">
                            <a href="{{ ($n->data['url'] ?? '#') ?: '#' }}" class="text-decoration-none text-body d-block">
                                <div class="d-flex gap-2">
                                    <i class="bi {{ $n->data['icon'] ?? 'bi-bell' }} text-primary"></i>
                                    <div>
                                        <div class="fw-bold small">{{ $n->data['title'] ?? 'اعلان' }}</div>
                                        <div class="small text-muted">{{ $n->data['body'] ?? '' }}</div>
                                        <div class="small text-muted mt-1">{{ fa_datetime($n->created_at) }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted small">اعلانی ندارید</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endcan

        <div class="dropdown">
            <a class="d-flex align-items-center gap-2 text-decoration-none" href="#" data-bs-toggle="dropdown" id="userMenu">
                <span class="avatar">{{ mb_substr($user->name, 0, 1) }}</span>
                <span class="d-none d-md-inline">
                    <span class="d-block small fw-bold">{{ $user->name }}</span>
                    <span class="d-block small text-muted">{{ $user->roles->pluck('name')->implode('، ') }}</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border">
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person ms-2"></i> پروفایل کاربری</a></li>
                <li><a class="dropdown-item" href="{{ route_if('shop.wallet') }}"><i class="bi bi-wallet2 ms-2"></i> کیف پول</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-in-left ms-2"></i> خروج</button>
                    </form>
                </li>
            </ul>
        </div>
    </header>

    <main class="key-content">
        @include('components.alerts')
        @yield('content')
    </main>

    <footer class="text-center text-muted small pb-4 no-print">
        سامانه کلید (KEY) — پلتفرم جامع مدیریت آموزش، آزمون، مشاوره و خدمات آموزشی
    </footer>
</div>

<div class="sidebar-overlay d-lg-none" style="display:none"></div>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/key.js') }}"></script>
@stack('scripts')
</body>
</html>
