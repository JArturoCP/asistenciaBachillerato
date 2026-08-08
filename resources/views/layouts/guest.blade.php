<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistema de Asistencia Escolar') }} - SIGO</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Bootstrap 5.3 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <!-- SweetAlert2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --wine-main: #8B1B3D;
                --wine-hover: #721430;
                --wine-light: rgba(139, 27, 61, 0.08);
                --bg-cream: #F5F1EA;
                --text-dark: #2B2B2B;
            }

            body {
                font-family: 'Outfit', sans-serif;
                background-color: var(--bg-cream);
                color: var(--text-dark);
                min-height: 100vh;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
            }

            /* Header Section */
            .auth-header-wrapper {
                background: linear-gradient(180deg, #8B1B3D 0%, #701330 100%);
                padding: 36px 20px 30px 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                width: 100%;
                box-sizing: border-box;
                border-bottom: 3px solid rgba(0,0,0,0.08);
            }

            .logo-box {
                background: #ffffff;
                border-radius: 20px;
                padding: 24px 28px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                max-width: 440px;
                width: calc(100% - 32px);
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto;
                box-sizing: border-box;
            }

            .logo-box img {
                max-height: 175px;
                width: auto;
                max-width: 100%;
                object-fit: contain;
                display: block;
                margin: 0 auto;
            }

            .auth-version {
                font-size: 0.82rem;
                color: rgba(255, 255, 255, 0.85);
                margin-top: 14px;
                letter-spacing: 0.5px;
                font-weight: 400;
            }

            /* Content Section & Spacing */
            .auth-content-wrapper {
                flex: 1;
                padding: 36px 16px 48px 16px;
                display: flex;
                justify-content: center;
                align-items: flex-start;
                width: 100%;
                box-sizing: border-box;
            }

            .auth-card {
                background: #ffffff;
                color: var(--text-dark);
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                width: 100%;
                max-width: 440px;
                padding: 32px 24px;
                border: 1px solid rgba(0,0,0,0.04);
                box-sizing: border-box;
            }

            @media (min-width: 576px) {
                .auth-card {
                    padding: 40px 36px;
                }
            }

            .btn-wine {
                background-color: var(--wine-main);
                color: #ffffff;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.2s ease-in-out;
            }

            .btn-wine:hover, .btn-wine:focus, .btn-wine:active {
                background-color: var(--wine-hover);
                color: #ffffff;
                box-shadow: 0 4px 12px rgba(139, 27, 61, 0.3);
            }

            .form-control:focus, .form-select:focus {
                border-color: var(--wine-main);
                box-shadow: 0 0 0 0.25rem var(--wine-light);
            }

            .form-label {
                font-weight: 500;
                color: #4a5568;
                margin-bottom: 6px;
            }
        </style>
    </head>
    <body>
        <div class="auth-header-wrapper">
            <div class="logo-box">
                <img src="{{ asset('images/logo.png') }}" alt="SIGO Logo">
            </div>
        </div>

        <div class="auth-content-wrapper">
            <div class="auth-card">
                @if(session('status'))
                    <div class="alert alert-info alert-dismissible fade show small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i> {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show small mb-3">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Corrija los errores marcados:</div>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </div>
        </div>

        <!-- Bootstrap 5.3 JS Bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SweetAlert2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </body>
</html>
