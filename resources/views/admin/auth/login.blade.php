<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PARSI Education Bot</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('ParsiLogo.jpg') }}" sizes="any">
    
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--card);
            border-radius: 16px;
            border: 1px solid var(--border);
            padding: 2.5rem 2rem;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .login-subtitle {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 0.35rem;
        }

        .login-form .form-group {
            margin-bottom: 1.25rem;
        }

        .login-form label {
            display: block;
            font-size: 0.825rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.4rem;
        }

        .login-form input[type="email"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #ffffff;
            font-size: 0.9rem;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .login-form input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.825rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 10px;
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: #ffffff;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
        }

        .login-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.775rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
            padding-top: 1.25rem;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        
        <div class="login-header">
            <img src="{{ asset('ParsiLogo.jpg') }}" alt="PARSI Logo" class="login-logo">
            <h1 class="login-title">PARSI Admin</h1>
            <p class="login-subtitle">Sign in to manage educational materials and bot services</p>
        </div>

        @if(session('info'))
            <div class="alert alert-info" style="margin-bottom: 1.25rem; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem;">
                ℹ️ {{ session('info') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error" style="margin-bottom: 1.25rem; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem;">
                ❌ {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error" style="margin-bottom: 1.25rem; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem;">
                @foreach($errors->all() as $error)
                    <div>⚠️ {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST" class="login-form">
            @csrf

            <div class="form-group">
                <label for="email">Admin Email or Username</label>
                <input 
                    type="text" 
                    id="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus 
                    placeholder="admin@parsi.polito.it or devbravee"
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="••••••••••••"
                    autocomplete="current-password"
                >
            </div>

            <div class="login-options">
                <label class="remember-label" for="remember">
                    <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <span>Keep me signed in</span>
                </label>
            </div>

            <button type="submit" class="btn-login">
                Sign In to Dashboard →
            </button>
        </form>

        <div class="login-footer">
            🔒 Protected by Politecnico di Torino PARSI Educational Committee
        </div>

    </div>
</div>

</body>
</html>
