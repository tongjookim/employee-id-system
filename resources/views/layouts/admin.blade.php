<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '사원증 관리시스템') - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { font-family: 'Pretendard', -apple-system, sans-serif; background: #f4f6f9; }
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-width);
            background: #1e293b; color: #fff; z-index: 1000; overflow-y: auto;
        }
        .sidebar .brand { padding: 1.5rem 1.2rem; font-size: 1.15rem; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar .nav-link {
            color: rgba(255,255,255,.7); padding: .7rem 1.2rem; display: flex; align-items: center; gap: .6rem;
            transition: all .15s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,.08); }
        .sidebar .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }
        .main-content { margin-left: var(--sidebar-width); padding: 1.5rem 2rem; min-height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.08); border-radius: .5rem; }
        .stat-card { text-align: center; padding: 1.5rem; }
        .stat-card .number { font-size: 2rem; font-weight: 700; color: #334155; }
        .stat-card .label { color: #64748b; font-size: .85rem; margin-top: .25rem; }
        .table th { font-weight: 600; color: #475569; background: #f8fafc; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform .3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- 사이드바 --}}
    <nav class="sidebar" id="sidebar">
        <div class="brand"><i class="bi bi-person-badge"></i> 사원증 관리</div>
        <div class="py-2">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> 대시보드
            </a>
            <a href="{{ route('admin.employees.index') }}" class="nav-link {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> 직원 관리
            </a>
            <a href="{{ route('admin.templates.index') }}" class="nav-link {{ request()->routeIs('admin.templates.*') ? 'active' : '' }}">
                <i class="bi bi-palette"></i> 디자인 관리
            </a>
            <hr class="border-secondary mx-3">
            <form action="{{ route('admin.logout') }}" method="POST" class="px-3">
                @csrf
                <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                    <i class="bi bi-box-arrow-left"></i> 로그아웃
                </button>
            </form>
        </div>
    </nav>

    {{-- 메인 콘텐츠 --}}
    <div class="main-content">
        <div class="top-bar">
            <div>
                <button class="btn btn-light d-md-none" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <i class="bi bi-list"></i>
                </button>
                <h4 class="d-inline mb-0 ms-2">@yield('page-title')</h4>
            </div>
            <div class="text-muted small">
                <i class="bi bi-person-circle"></i> {{ session('admin_name', '관리자') }}
            </div>
        </div>

        {{-- 알림 메시지 --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // CSRF 토큰 글로벌 설정
        window.csrfToken = '{{ csrf_token() }}';
    </script>
    @stack('scripts')
</body>
</html>
