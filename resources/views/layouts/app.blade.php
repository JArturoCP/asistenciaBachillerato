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

        <!-- Vite Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                font-family: 'Outfit', sans-serif;
                background-color: #f4f6f9;
                color: #2c3e50;
            }
            .navbar-custom {
                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            }
            .badge-role-admin { background-color: #ef4444; color: white; }
            .badge-role-teacher { background-color: #3b82f6; color: white; }
            .badge-role-parent { background-color: #10b981; color: white; }
            .card-custom {
                border: none;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            }
            .table-custom th {
                background-color: #f8fafc;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.05em;
                color: #64748b;
            }
        </style>
    </head>
    <body>
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow-sm border-bottom py-3">
                    <div class="container">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="py-4">
                <div class="container">

                    {{-- Page-level error alerts --}}
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show card-custom mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any() && !old('form_modal_id'))
                        <div class="alert alert-danger alert-dismissible fade show card-custom mb-4" role="alert">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Atención: Por favor corrija los siguientes errores:</div>
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
            </main>
        </div>

        <!-- Bootstrap 5.3 JS Bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SweetAlert2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // SweetAlert ONLY for success notifications
                @if(session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: '¡Operación Exitosa!',
                        text: "{{ session('success') }}",
                        timer: 3500,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                @endif

                // Auto-reopen ONLY the specific modal that had validation errors
                @if($errors->any() && old('form_modal_id'))
                    const targetModalId = "{{ old('form_modal_id') }}";
                    const modalEl = document.getElementById(targetModalId);
                    if (modalEl) {
                        const bsModal = new bootstrap.Modal(modalEl);
                        bsModal.show();
                    }
                @endif
            });

            // SweetAlert ONLY for action confirmation (delete)
            function confirmDelete(event, message = '¿Está seguro de eliminar este registro?') {
                event.preventDefault();
                const form = event.target.closest('form');
                
                Swal.fire({
                    title: '¿Confirmar eliminación?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
                return false;
            }
        </script>
    </body>
</html>
