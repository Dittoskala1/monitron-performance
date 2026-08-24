{{-- resources/views/auth/forgot-password.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Monitoring Alat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: #e8ecf0;
            position: relative;
            overflow: hidden;
        }

        .background-image {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url('{{ asset('image/Injourney.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: 0;
            filter: brightness(0.7) saturate(0.6);
            transform: scale(1.05);
        }

        .background-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg,
                rgba(40, 40, 50, 0.6) 0%,
                rgba(60, 60, 70, 0.5) 50%,
                rgba(40, 40, 50, 0.6) 100%
            );
            z-index: 1;
        }

        .login-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 45px 40px 40px;
            box-shadow:
                0 30px 80px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .login-card .card-title {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            font-weight: 400;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 8px;
            opacity: 0.8;
        }

        .login-card .card-subtitle {
            color: rgba(255, 255, 255, 0.45);
            font-size: 13px;
            font-weight: 300;
            line-height: 1.6;
            margin-bottom: 26px;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            color: rgba(255, 255, 255, 0.5);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 8px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 14px 18px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.2);
            font-weight: 300;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.10);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.04);
            color: #fff;
            outline: none;
        }

        .btn-signin {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            font-size: 15px;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin-top: 8px;
            box-shadow: 0 4px 20px rgba(75, 85, 99, 0.3);
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 35px rgba(75, 85, 99, 0.35);
            background: linear-gradient(135deg, #5a6270, #3d4553);
            color: #fff;
        }

        .alert {
            background: rgba(200, 50, 50, 0.08);
            border: 1px solid rgba(200, 50, 50, 0.10);
            color: rgba(255, 200, 200, 0.8);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .alert i { margin-right: 8px; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.55);
            text-decoration: none;
            margin-top: 22px;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: rgba(255, 255, 255, 0.9);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 24px 30px;
                border-radius: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="background-image"></div>
    <div class="background-overlay"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="card-title">BUAT PASSWORD BARU</div>
            <div class="card-subtitle">
                Masukkan username Anda, lalu tentukan password baru untuk akun tersebut.
            </div>

            @if ($errors->any())
                <div class="alert">
                    <i class="bi bi-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('password.reset') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-control"
                           placeholder="Masukkan username Anda"
                           value="{{ old('username') }}"
                           required>
                </div>

                <div class="form-group">
                    <label for="password">Password Baru</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           placeholder="Minimal 6 karakter"
                           required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="form-control"
                           placeholder="Ulangi password baru"
                           required>
                </div>

                <button type="submit" class="btn-signin">Simpan Password Baru</button>

                <div class="text-center">
                    <a href="{{ route('login') }}" class="back-link">
                        <i class="bi bi-arrow-left"></i> Kembali ke halaman login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>