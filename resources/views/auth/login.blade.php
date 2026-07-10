<!DOCTYPE html>
<html class="light" lang="id">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="description" content="Login ke Sistem Informasi Akademik SDIT Darul Fikri">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}?v=2">
    <title>Login - SDIT Darul Fikri</title>

    {{-- Preconnect to font origins --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Critical CSS inlined for instant first paint --}}
    <style>
        /* Critical CSS - inlined to eliminate render-blocking */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            background: linear-gradient(to bottom, #ffffff 30%, #aadaf5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            -webkit-font-smoothing: antialiased;
        }

        .login-wrapper {
            position: relative;
            width: 100%;
            max-width: 26rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 5rem;
            height: 5rem;
            margin-bottom: 1rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.025em;
        }

        .login-subtitle {
            color: #6b7280;
            font-size: 0.7rem;
            margin-top: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        .login-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 2.5rem 2rem;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .login-card h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #111827;
        }

        .login-card .sub {
            color: #6b7280;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .form-group {
            margin-top: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.65rem;
            font-weight: 700;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1.15rem;
            height: 1.15rem;
            color: #6b7280;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            color: #111827;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        }

        .form-input:focus {
            background: #ffffff;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .remember-row label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.8rem;
            color: #4b5563;
        }

        .remember-row input[type=checkbox] {
            width: 0.875rem;
            height: 0.875rem;
            border-radius: 0.2rem;
            accent-color: #d97706;
            border: 1px solid #d1d5db;
        }

        .login-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem;
            margin-top: 1.5rem;
            background: #dc2626;
            color: #fff;
            font-weight: 600;
            font-size: 0.875rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }

        .login-btn:hover {
            background: #b91c1c;
        }

        .login-btn:active {
            transform: scale(0.98);
        }

        .login-btn svg {
            width: 1.15rem;
            height: 1.15rem;
        }

        .copyright {
            text-align: center;
            color: #4b5563;
            font-size: 0.7rem;
            margin-top: 2rem;
            font-weight: 500;
        }

        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .error-box svg {
            width: 1.15rem;
            height: 1.15rem;
            flex-shrink: 0;
            color: #ef4444;
        }
    </style>

    {{-- Load full Tailwind CSS asynchronously (non-render-blocking) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Inter font - preload non-render-blocking --}}
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&display=swap"
        as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&display=swap" rel="stylesheet">
    </noscript>
</head>

<body>

    <div class="login-wrapper">
        {{-- Logo & Header --}}
        <div class="login-header">
            <div class="login-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo SDIT Darul Fikri"
                    style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            <h1 class="login-title">SDIT Darul Fikri</h1>
            <p class="login-subtitle">Sistem Informasi Akademik</p>
        </div>

        {{-- Login Card --}}
        <div class="login-card">
            <div>
                <h2>Selamat Datang! </h2>
                <p class="sub">Silakan login untuk mengakses sistem</p>
            </div>

            @if ($errors->any())
                <div class="error-box" style="margin-top:1.5rem">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
                        <path
                            d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                {{-- Username --}}
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
                            <path
                                d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Z" />
                        </svg>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required
                            autofocus class="form-input" placeholder="Masukkan username" />
                    </div>
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
                            <path
                                d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm240-200q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80Z" />
                        </svg>
                        <input id="password" name="password" type="password" required class="form-input"
                            placeholder="Masukkan password" />
                    </div>
                </div>

                {{-- Remember & Login --}}
                <div class="remember-row">
                    <label>
                        <input name="remember" type="checkbox" />
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="login-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="currentColor">
                        <path
                            d="M480-120v-80h280v-560H480v-80h280q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H480Zm-80-160-55-58 102-102H120v-80h327L345-622l55-58 200 200-200 200Z" />
                    </svg>
                    Masuk ke Sistem
                </button>
            </form>



        </div>

        {{-- Footer --}}
        <p class="copyright">
            &copy; 2026 SDIT Darul Fikri V2.0, <br>Semua Hak Dilindungi.<br>
        </p>
    </div>
</body>

</html>
