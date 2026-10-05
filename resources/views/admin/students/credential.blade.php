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
        /* Dedicated PVC ID Card Printer Specifications (5.4 cm width x 8.6 cm height) */
        @page {
            size: 54mm 86mm;
            margin: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #1a0b12;
            min-height: 100vh;
            margin: 0;
            padding: 80px 20px 100px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .no-print-bar {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            padding-top: 15px;
            z-index: 9999;
        }

        /* Screen Display: Enlarge Credentials on Monitor for easy reading */
        .credentials-wrapper {
            display: flex;
            flex-direction: column;
            gap: 35px;
            align-items: center;
            justify-content: center;
            transform: scale(1.65);
            transform-origin: top center;
            margin-top: 25px;
            margin-bottom: 240px;
        }

        @media (min-width: 992px) {
            .credentials-wrapper {
                flex-direction: row;
                gap: 50px;
                margin-bottom: 150px;
            }
        }

        .card-side-label {
            color: #D4AF37;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 6px;
            text-align: center;
        }

        .card-print-page {
            width: 5.4cm;
            height: 8.6cm;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Exact PVC Card Specs: 5.4 cm width x 8.6 cm height */
        .id-card {
            width: 5.4cm;
            height: 8.6cm;
            background: #ffffff;
            border-radius: 3.5mm;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            border: 1.5px solid #D4AF37;
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .top-stripe-guinda {
            height: 2.5mm;
            background-color: #70122B;
            width: 100%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .top-stripe-gold {
            height: 1.2mm;
            background-color: #D4AF37;
            width: 100%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .id-card-header {
            background: #ffffff;
            color: #1e293b;
            padding: 3px 4px 2px 4px;
            text-align: center;
            position: relative;
        }

        .school-logo {
            max-height: 22px;
            width: auto;
            max-width: 85%;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .header-school-name {
            color: #70122B;
            font-size: 0.42rem;
            letter-spacing: 0.2px;
            line-height: 1.1;
            font-weight: 700;
            margin-top: 1px;
        }

        .header-title {
            font-size: 0.52rem;
            line-height: 1.1;
            margin-top: 1px;
            font-weight: 700;
            color: #B8860B;
        }

        .header-cycle {
            color: #64748b;
            font-size: 0.38rem;
            display: block;
        }

        .header-divider-gold {
            height: 1.5px;
            background: linear-gradient(90deg, #70122B 0%, #D4AF37 50%, #70122B 100%);
            width: 100%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .id-card-body {
            padding: 4px 5px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-evenly;
            background: #ffffff;
        }

        .student-photo {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #D4AF37;
            box-shadow: 0 2px 6px rgba(112, 18, 43, 0.2);
        }

        .student-avatar-fallback {
            width: 42px;
            height: 42px;
            font-size: 1.4rem;
            border: 2px solid #D4AF37;
            background-color: rgba(112, 18, 43, 0.08);
            color: #70122B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .student-name {
            color: #4A0818;
            font-weight: 700;
            font-size: 0.56rem;
            line-height: 1.15;
            text-align: center;
            margin: 1px 0;
            word-break: break-word;
        }

        .student-subinfo {
            font-size: 0.44rem;
            color: #475569;
        }

        .badge-guinda {
            background-color: #70122B !important;
            color: #ffffff !important;
            font-size: 0.40rem;
            padding: 1px 5px;
            border-radius: 8px;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .badge-dorado {
            background-color: #D4AF37 !important;
            color: #3B0513 !important;
            font-size: 0.40rem;
            padding: 1px 5px;
            border-radius: 8px;
            font-weight: 700;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .qr-box {
            background: #FAF7ED;
            border: 1px dashed #D4AF37;
            border-radius: 5px;
            padding: 2px;
            display: inline-block;
            line-height: 0;
        }

        .privacy-badge {
            background-color: #70122B;
            color: #F3C649;
            font-size: 0.38rem;
            border-top: 1px solid #D4AF37;
            padding: 2px 3px;
            font-weight: 500;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .institutional-stamp-signature {
            width: 100%;
            max-height: 6.8cm;
            object-fit: contain;
            display: block;
            margin: auto;
        }

        .id-card-back .id-card-body {
            justify-content: center;
            padding: 4px 5px;
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

        /* Print: 2 exact PVC pages, 54 mm x 86 mm, without screen scaling */
        @media print {
            html, body {
                width: 54mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print,
            .no-print-bar,
            .card-side-label {
                display: none !important;
            }

            .credentials-wrapper {
                display: block !important;
                width: 54mm !important;
                margin: 0 !important;
                padding: 0 !important;
                gap: 0 !important;
                transform: none !important;
            }

            .card-print-page {
                display: block !important;
                width: 54mm !important;
                height: 86mm !important;
                min-width: 54mm !important;
                min-height: 86mm !important;
                max-width: 54mm !important;
                max-height: 86mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .card-print-page-front {
                break-after: page !important;
                page-break-after: always !important;
            }

            .card-print-page-back {
                break-before: auto !important;
                page-break-before: auto !important;
                break-after: auto !important;
                page-break-after: auto !important;
            }

            .id-card {
                width: 54mm !important;
                height: 86mm !important;
                min-width: 54mm !important;
                min-height: 86mm !important;
                max-width: 54mm !important;
                max-height: 86mm !important;
                margin: 0 !important;
                padding: 0 !important;
                transform: none !important;
                box-shadow: none !important;
                box-sizing: border-box !important;
                border-radius: 3.5mm !important;
                border: 1.5px solid #D4AF37 !important;
                overflow: hidden !important;
            }
        }
    </style>
</head>
<body>

    <div class="text-center no-print-bar no-print">
        <button onclick="window.print()" class="btn btn-gold btn-sm shadow px-3 py-2 fs-6">
            <i class="bi bi-printer-fill me-1"></i> Imprimir Credencial (2 páginas PVC 54 × 86 mm)
        </button>
        <button onclick="window.close()" class="btn btn-guinda btn-sm shadow ms-2 px-3 py-2 fs-6">
            <i class="bi bi-x-lg me-1"></i> Cerrar
        </button>
        <div class="text-white-50 mt-2" style="font-size: .78rem;">
            Al imprimir: escala 100 %, márgenes ninguno y desactivar encabezados/pies de página.
        </div>
    </div>

    <!-- Credentials Container (Scaled on screen, 5.4 cm x 8.6 cm on print) -->
    <div class="credentials-wrapper">
        
        <!-- PAGE 1: FRONT SIDE (Frente Estudiante) -->
        <div class="card-print-page card-print-page-front">
            <div class="card-side-label no-print"><i class="bi bi-card-heading me-1"></i> Frente (5.4 cm x 8.6 cm)</div>
            <div class="id-card id-card-front">
                <div class="top-stripe-guinda"></div>
                <div class="top-stripe-gold"></div>

                <div class="id-card-header">
                    <div>
                        <img src="{{ asset('images/logo_escuela2.jpeg') }}" alt="Logo Escuela" class="school-logo">
                    </div>
                    <div class="text-uppercase header-school-name">Escuela Preparatoria Oficial No. 112</div>
                    <div class="header-title">Credencial Estudiantil</div>
                    <span class="header-cycle">Ciclo Escolar {{ $student->grupo->ciclo_escolar }}</span>
                </div>

                <div class="header-divider-gold"></div>

                <div class="id-card-body">
                    <div class="my-1">
                        @if($student->foto)
                            <img src="{{ asset('storage/' . $student->foto) }}" alt="Foto de {{ $student->nombre_completo }}" class="student-photo">
                        @else
                            <div class="student-avatar-fallback">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        @endif
                    </div>

                    <h5 class="student-name">{{ $student->nombre_completo }}</h5>
                    <div class="student-subinfo">Matrícula: <strong class="text-dark font-monospace">{{ $student->matricula }}</strong></div>

                    <div class="d-flex justify-content-center gap-1 my-1">
                        <span class="badge badge-guinda">{{ $student->grupo->nombre_visible }}</span>
                        <span class="badge badge-dorado">Matutino</span>
                    </div>

                    <div class="qr-box">
                        {!! $qrCodeSvg !!}
                    </div>

                    <div class="text-muted font-monospace" style="font-size: 0.38rem;">
                        UUID: {{ $student->uuid }}
                    </div>
                </div>

                <div class="privacy-badge">
                    <i class="bi bi-shield-check me-1" style="color: #F3C649;"></i>
                    Código QR seudonomizado de acceso
                </div>
            </div>
        </div>

        <!-- PAGE 2: BACK SIDE (Reverso Estudiante con Firma del Director) -->
        <div class="card-print-page card-print-page-back">
            <div class="card-side-label no-print"><i class="bi bi-card-text me-1"></i> Reverso (5.4 cm x 8.6 cm)</div>
            <div class="id-card id-card-back">
                <div class="top-stripe-guinda"></div>
                <div class="top-stripe-gold"></div>

                <div class="id-card-header py-1">
                    <div class="text-uppercase header-school-name fw-bold" style="font-size: 0.44rem;">Escuela Preparatoria Oficial No. 112</div>
                    <div class="text-muted font-monospace" style="font-size: 0.38rem;">CCT: 15EBH0214P | Matutino</div>
                    <div class="badge badge-guinda mt-1" style="font-size: 0.38rem;">DATOS INSTITUCIONALES</div>
                </div>

                <div class="header-divider-gold"></div>

                <div class="id-card-body p-2">
                    <img
                        src="{{ asset('images/sello_firma_credencial.jpg') }}"
                        alt="Sello institucional y firma de Dirección Escolar"
                        class="institutional-stamp-signature"
                    >
                </div>

                <div class="privacy-badge">
                    <i class="bi bi-shield-lock-fill me-1" style="color: #F3C649;"></i>
                    Vigencia: Ciclo Escolar {{ $student->grupo->ciclo_escolar }}
                </div>
            </div>
        </div>

    </div>

</body>
</html>
