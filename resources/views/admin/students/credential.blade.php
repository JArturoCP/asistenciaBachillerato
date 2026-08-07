<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credencial Estudiantil - {{ $student->nombre_completo }}</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #e2e8f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        /* Printable ID Card Dimensions (Standard CR80 size ratio) */
        .id-card {
            width: 380px;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid #cbd5e1;
            position: relative;
        }

        .id-card-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 16px;
            text-align: center;
            border-bottom: 4px solid #f59e0b;
        }

        .id-card-body {
            padding: 20px;
        }

        .student-photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #f59e0b;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .qr-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 10px;
            display: inline-block;
        }

        .privacy-badge {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 0.7rem;
            border-top: 1px solid #e2e8f0;
            padding: 10px 16px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .id-card {
                box-shadow: none;
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body>

    <div class="text-center no-print mb-4 position-fixed top-0 start-50 translate-middle-x pt-3 z-3">
        <button onclick="window.print()" class="btn btn-warning shadow fw-bold">
            <i class="bi bi-printer-fill me-1"></i> Imprimir Credencial
        </button>
        <button onclick="window.close()" class="btn btn-dark shadow ms-2">
            <i class="bi bi-x-lg me-1"></i> Cerrar
        </button>
    </div>

    <div class="id-card">
        <!-- Header -->
        <div class="id-card-header">
            <div class="fw-bold text-uppercase tracking-wider small text-warning">Escuela Bachillerato Oficial</div>
            <h6 class="mb-0 fw-bold">Credencial Estudiantil</h6>
            <small class="text-white-50">Ciclo Escolar {{ $student->grupo->ciclo_escolar }}</small>
        </div>

        <!-- Body -->
        <div class="id-card-body text-center">
            <!-- Student Photo / Avatar -->
            <div class="mb-3">
                @if($student->foto)
                    <img src="{{ asset('storage/' . $student->foto) }}" alt="Foto de {{ $student->nombre_completo }}" class="student-photo">
                @else
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 85px; height: 85px; font-size: 2.5rem; border: 3px solid #cbd5e1;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                @endif
            </div>

            <!-- Student Info -->
            <h5 class="fw-bold text-dark mb-1">{{ $student->nombre_completo }}</h5>
            <div class="text-muted small mb-2">Matrícula: <strong class="text-dark font-monospace">{{ $student->matricula }}</strong></div>

            <div class="d-flex justify-content-center gap-2 mb-3">
                <span class="badge bg-primary px-3 py-1">Grupo {{ $student->grupo->codigo_grupo }}</span>
                <span class="badge bg-secondary px-3 py-1">Turno {{ ucfirst($student->grupo->turno) }}</span>
            </div>

            <!-- Pseudonymized QR Code -->
            <div class="qr-box mb-2">
                {!! $qrCodeSvg !!}
            </div>

            <div class="small text-muted font-monospace" style="font-size: 0.65rem;">
                UUID: {{ $student->uuid }}
            </div>
        </div>

        <!-- Privacy Footer (LFPDPPP) -->
        <div class="privacy-badge text-center">
            <i class="bi bi-shield-check text-success me-1"></i>
            Código QR seudonomizado de acceso. No contiene CURP ni datos sensibles visuales (Cumplimiento LFPDPPP México).
        </div>
    </div>

</body>
</html>
