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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #1a0b12;
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
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 14px 35px rgba(0, 0, 0, 0.3);
            border: 2px solid #D4AF37;
            position: relative;
        }

        .top-stripe-guinda {
            height: 10px;
            background-color: #70122B;
            width: 100%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .top-stripe-gold {
            height: 4px;
            background-color: #D4AF37;
            width: 100%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .id-card-header {
            background: #ffffff;
            color: #1e293b;
            padding: 18px 20px 14px 20px;
            text-align: center;
            position: relative;
        }

        .school-logo {
            max-height: 75px;
            width: auto;
            max-width: 85%;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .header-school-name {
            color: #70122B;
            font-size: 0.82rem;
            letter-spacing: 0.8px;
            line-height: 1.25;
            font-weight: 700;
            margin-top: 6px;
        }

        .header-title {
            font-size: 1.15rem;
            line-height: 1.25;
            margin-top: 2px;
            font-weight: 700;
            color: #B8860B;
        }

        .header-cycle {
            color: #64748b;
            font-size: 0.72rem;
            margin-top: 2px;
            display: block;
        }

        .header-divider-gold {
            height: 3px;
            background: linear-gradient(90deg, #70122B 0%, #D4AF37 50%, #70122B 100%);
            width: 100%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .id-card-body {
            padding: 22px 20px 18px 20px;
            background: #ffffff;
        }

        .student-photo {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border-radius: 50%;
            border: 3.5px solid #D4AF37;
            box-shadow: 0 4px 15px rgba(112, 18, 43, 0.2);
        }

        .student-avatar-fallback {
            width: 95px;
            height: 95px;
            font-size: 2.7rem;
            border: 3.5px solid #D4AF37;
            background-color: rgba(112, 18, 43, 0.08);
            color: #70122B;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.25);
        }

        .student-name {
            color: #4A0818;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .badge-guinda {
            background-color: #70122B !important;
            color: #ffffff !important;
            border: 1px solid #D4AF37;
            font-weight: 600;
            font-size: 0.78rem;
            border-radius: 20px;
        }

        .badge-dorado {
            background-color: #D4AF37 !important;
            color: #3B0513 !important;
            font-weight: 700;
            font-size: 0.78rem;
            border-radius: 20px;
        }

        .qr-box {
            background: #FAF7ED;
            border: 2px dashed #D4AF37;
            border-radius: 14px;
            padding: 12px;
            display: inline-block;
        }

        .privacy-badge {
            background-color: #70122B;
            color: #F3C649;
            font-size: 0.68rem;
            border-top: 2px solid #D4AF37;
            padding: 10px 16px;
            font-weight: 500;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .btn-gold {
            background-color: #D4AF37;
            color: #3B0513;
            font-weight: 700;
            border: none;
            transition: all 0.2s ease-in-out;
        }
        .btn-gold:hover {
            background-color: #b89224;
            color: #ffffff;
        }

        .btn-guinda {
            background-color: #4A0818;
            color: #ffffff;
            font-weight: 600;
            border: 1px solid #D4AF37;
            transition: all 0.2s ease-in-out;
        }
        .btn-guinda:hover {
            background-color: #70122B;
            color: #ffffff;
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
                border: 1.5px solid #D4AF37;
            }
        }
    </style>
</head>
<body>

    <div class="text-center no-print mb-4 position-fixed top-0 start-50 translate-middle-x pt-3 z-3">
        <button onclick="window.print()" class="btn btn-gold shadow">
            <i class="bi bi-printer-fill me-1"></i> Imprimir Credencial
        </button>
        <button onclick="window.close()" class="btn btn-guinda shadow ms-2">
            <i class="bi bi-x-lg me-1"></i> Cerrar
        </button>
    </div>

    <div class="id-card">
        <!-- Top Institutional Accent Stripes -->
        <div class="top-stripe-guinda"></div>
        <div class="top-stripe-gold"></div>

        <!-- Header Section (White background to seamlessly camouflage white-background logos) -->
        <div class="id-card-header">
            <div class="mb-2">
                <img src="{{ asset('images/logo_escuela2.jpeg') }}" alt="Logo Escuela" class="school-logo">
            </div>
            <div class="text-uppercase header-school-name">Escuela Preparatoria Oficial número 112</div>
            <h6 class="mb-0 header-title">Credencial Estudiantil</h6>
            <span class="header-cycle">Ciclo Escolar {{ $student->grupo->ciclo_escolar }}</span>
        </div>

        <!-- Golden Divider -->
        <div class="header-divider-gold"></div>

        <!-- Body -->
        <div class="id-card-body text-center">
            <!-- Student Photo / Avatar -->
            <div class="mb-3">
                @if($student->foto)
                    <img src="{{ asset('storage/' . $student->foto) }}" alt="Foto de {{ $student->nombre_completo }}" class="student-photo">
                @else
                    <div class="rounded-circle student-avatar-fallback d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-person-fill"></i>
                    </div>
                @endif
            </div>

            <!-- Student Info -->
            <h5 class="student-name mb-1">{{ $student->nombre_completo }}</h5>
            <div class="text-muted small mb-2">Matrícula: <strong class="text-dark font-monospace">{{ $student->matricula }}</strong></div>

            <div class="d-flex justify-content-center gap-2 mb-3">
                <span class="badge badge-guinda px-3 py-1.5">Grupo {{ $student->grupo->codigo_grupo }}</span>
                <span class="badge badge-dorado px-3 py-1.5">Turno {{ ucfirst($student->grupo->turno) }}</span>
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
            <i class="bi bi-shield-check me-1" style="color: #F3C649;"></i>
            Código QR seudonomizado de acceso.
        </div>
    </div>

</body>
</html>
