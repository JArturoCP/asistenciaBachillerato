<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistema de Asistencia Escolar') }}</title>

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
            body {
                font-family: 'Outfit', sans-serif;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                color: #f8fafc;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px 0;
            }
            .auth-card {
                background: #ffffff;
                color: #1e293b;
                border-radius: 16px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
                overflow: hidden;
                width: 100%;
                max-width: 460px;
            }
            .auth-header {
                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                color: #ffffff;
                padding: 24px;
                text-align: center;
                border-bottom: 4px solid #f59e0b;
            }
        </style>
    </head>
    <body>
        <div class="auth-card">
            <div class="auth-header">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="bi bi-qr-code-scan text-warning fs-1 me-2"></i>
                </div>
                <h5 class="fw-bold mb-0">Control de Asistencia Escolar</h5>
                <small class="text-white-50">Bachillerato - Protección LFPDPPP</small>
            </div>
            <div class="p-4">
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

                {{ $slot }}
            </div>
        </div>

        <!-- Bootstrap 5.3 JS Bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SweetAlert2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </body>
</html>
