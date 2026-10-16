<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - PARSI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('ParsiLogo.jpg') }}" sizes="any">
    
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>

    <!-- Navigation Header -->
    <nav class="navbar">
        <a href="{{ route('admin.dashboard') }}" class="nav-brand">
            <img src="{{ asset('ParsiLogo.jpg') }}" alt="PARSI Logo">
            <span>PARSI Admin</span>
        </a>

        <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <ul class="nav-links">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    📊 Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('admin.academic-years.index') }}" class="nav-link {{ request()->routeIs('admin.academic-years.*') ? 'active' : '' }}">
                    📅 Academic Years
                </a>
            </li>
            <li>
                <a href="{{ route('admin.programs.index') }}" class="nav-link {{ request()->routeIs('admin.programs.*') ? 'active' : '' }}">
                    🎓 Programmes
                </a>
            </li>
            <li>
                <a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                    📖 Courses
                </a>
            </li>
            <li>
                <a href="{{ route('admin.resource-types.index') }}" class="nav-link {{ request()->routeIs('admin.resource-types.*') ? 'active' : '' }}">
                    🏷️ Resource Types
                </a>
            </li>
             <li>
                <a href="{{ route('admin.resources.index') }}" class="nav-link {{ request()->routeIs('admin.resources.*') ? 'active' : '' }}">
                    📚 Resources
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content Area -->
    <main class="main-wrapper">
        @if(session('success'))
            <div class="alert alert-success">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                ❌ {{ session('error') }}
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info">
                ℹ️ {{ session('info') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <div>
                    <strong>Please check the following errors:</strong>
                    <ul style="margin-top: 0.35rem; margin-left: 1rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const navToggle = document.getElementById('navToggle');
            const navLinks = document.querySelector('.nav-links');
            if (navToggle && navLinks) {
                navToggle.addEventListener('click', function () {
                    navLinks.classList.toggle('open');
                });
            }
        });
    </script>
</body>
</html>
