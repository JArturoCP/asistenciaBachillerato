<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credencial Estudiantil - {{ $student->nombre_completo }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        @page {
            size: 54mm 86mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #1a0b12;
            min-height: 100vh;
            padding: 80px 20px 100px;
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
            font-size: 0.82rem;
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

        .id-card .text-muted {
            color: #000000 !important;
        }

        .id-card {
            width: 5.4cm;
            height: 8.6cm;
            background: #ffffff;
            border-radius: 3.5mm;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            border: 1.5px solid #D4AF37;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .top-stripe-guinda {
            flex: 0 0 2.5mm;
            height: 2.5mm;
            background-color: #70122B;
            width: 100%;
        }

        .top-stripe-gold {
            flex: 0 0 1.2mm;
            height: 1.2mm;
            background-color: #D4AF37;
            width: 100%;
        }

        .id-card-header {
            flex: 0 0 auto;
            background: #ffffff;
            color: #1e293b;
            padding: 4px 4px 3px;
            text-align: center;
        }

        .header-school-name {
            color: #70122B;
            font-size: 0.56rem;
            letter-spacing: 0.15px;
            line-height: 1.1;
            font-weight: 700;
        }

        .header-title {
            font-size: 0.68rem;
            line-height: 1.05;
            margin-top: 2px;
            font-weight: 700;
            color: #B8860B;
        }

        .header-cycle {
            color: #000000;
            font-size: 0.50rem;
            font-weight: 600;
            line-height: 1;
            display: block;
            margin-top: 2px;
        }

        .header-divider-gold {
            flex: 0 0 1.5px;
            height: 1.5px;
            background: linear-gradient(90deg, #70122B 0%, #D4AF37 50%, #70122B 100%);
            width: 100%;
        }

        /* Frente: Bootstrap Grid. CSS sólo controla dimensiones físicas/visuales. */
        .front-body {
            flex: 1 1 auto;
            min-height: 0;
            padding: 5px 5px 6px;
            background: #ffffff;
            overflow: hidden;
        }

        .front-student-row {
            min-height: 35mm;
        }

        .front-photo-column {
            min-width: 0;
        }

        .student-photo {
            width: 100%;
            max-width: 20mm;
            height: 27mm;
            object-fit: cover;
            object-position: center top;
            border-radius: 2mm;
            border: 1.5px solid #D4AF37;
            box-shadow: 0 1px 5px rgba(112, 18, 43, 0.18);
            background: #f8fafc;
        }

        .student-avatar-fallback {
            width: 100%;
            max-width: 20mm;
            height: 27mm;
            border-radius: 2mm;
            border: 1.5px solid #D4AF37;
            background-color: rgba(112, 18, 43, 0.06);
            color: #70122B;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .student-role {
            margin-top: 3px;
            color: #70122B;
            font-size: 0.42rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.35px;
            text-align: center;
        }

        .front-info-column {
            min-width: 0;
            padding-top: 1px;
        }

        .student-name {
            color: #4A0818;
            font-weight: 700;
            font-size: 0.72rem;
            line-height: 1.08;
            text-align: left;
            margin: 0 0 3px;
            overflow-wrap: anywhere;
            text-transform: uppercase;
        }

        .student-data-box {
            border-left: 2px solid #D4AF37;
            padding-left: 4px;
            margin-bottom: 3px;
        }

        .student-data-label {
            display: block;
            color: #000000;
            font-size: 0.47rem;
            font-weight: 700;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            margin-bottom: 1px;
        }

        .student-data-value {
            display: block;
            color: #1e293b;
            font-size: 0.52rem;
            line-height: 1.08;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .group-name {
            color: #70122B;
            font-size: 0.58rem;
        }

        .shift-badge {
            display: inline-block;
            background: #D4AF37;
            color: #3B0513;
            border-radius: 8px;
            padding: 1px 5px;
            font-size: 0.43rem;
            line-height: 1.25;
            font-weight: 700;
            margin-top: 1px;
        }

        .qr-row {
            margin-top: 2px;
        }

        .qr-area {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-box {
            width: 21mm;
            height: 21mm;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 1px solid #D4AF37;
            border-radius: 1.5mm;
            padding: 1.2mm;
            line-height: 0;
        }

        .qr-box svg {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }

        .qr-caption {
            margin-top: 2px;
            color: #000000;
            font-size: 0.42rem;
            line-height: 1;
            text-align: center;
            font-weight: 500;
        }

        /* Reverso */
        .back-body {
            flex: 1 1 auto;
            min-height: 0;
            padding: 5px 5px 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }

        .credential-cct {
            color: #000000;
            font-size: 0.52rem;
            line-height: 1.05;
            font-weight: 700;
        }

        .seal-title {
            color: #70122B;
            font-size: 0.50rem;
            line-height: 1;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            margin: 1px 0 4px;
        }

        .school-seal-frame {
            width: 44mm;
            height: 24mm;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #ffffff;
        }

        .school-seal-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .director-block {
            width: 44mm;
            text-align: center;
            margin: 2px auto 1px;
        }

        .director-signature-frame {
            width: 38mm;
            height: 15mm;
            margin: 0 auto -1px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            overflow: hidden;
        }

        .director-signature-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .director-line {
            width: 32mm;
            height: 1px;
            background: #70122B;
            margin: 0 auto 2px;
        }

        .director-label {
            color: #000000;
            font-size: 0.47rem;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            font-weight: 600;
        }

        .director-name {
            margin-top: 2px;
            color: #4A0818;
            font-size: 0.62rem;
            line-height: 1.1;
            font-weight: 700;
        }

        .credential-footer {
            flex: 0 0 auto;
            background-color: #70122B;
            color: #F3C649;
            font-size: 0.43rem;
            border-top: 1px solid #D4AF37;
            padding: 2px 3px;
            font-weight: 600;
            text-align: center;
        }

        .btn-gold {
            background-color: #D4AF37;
            color: #3B0513;
            font-weight: 700;
            border: none;
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
        }

        .btn-guinda:hover {
            background-color: #70122B;
            color: #ffffff;
        }

        /* Impresión: exactamente 2 páginas PVC de 54 x 86 mm */
        @media print {
            html,
            body {
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
            Al imprimir: escala 100 %, márgenes ninguno, gráficos de fondo activados y encabezados/pies desactivados.
        </div>
    </div>

    <div class="credentials-wrapper">

        <!-- PÁGINA 1: FRENTE -->
        <div class="card-print-page card-print-page-front">
            <div class="card-side-label no-print">
                <i class="bi bi-card-heading me-1"></i> Frente (5.4 cm × 8.6 cm)
            </div>

            <div class="id-card id-card-front">
                <div class="top-stripe-guinda"></div>
                <div class="top-stripe-gold"></div>

                <div class="id-card-header">
                    <div class="text-uppercase header-school-name">Escuela Preparatoria Oficial No. 112</div>
                    <div class="header-title">Credencial Estudiantil</div>
                    <span class="header-cycle">Ciclo Escolar {{ $student->grupo->ciclo_escolar }}</span>
                </div>

                <div class="header-divider-gold"></div>

                <div class="front-body container-fluid">
                    {{-- FILA 1: fotografía + datos del estudiante --}}
                    <div class="row g-1 align-items-start front-student-row">
                        <div class="col-5 text-center front-photo-column">
                            @if($student->foto)
                                <img
                                    src="{{ asset('storage/' . $student->foto) }}"
                                    alt="Foto de {{ $student->nombre_completo }}"
                                    class="student-photo img-fluid"
                                >
                            @else
                                <div class="student-avatar-fallback mx-auto">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            @endif

                            <div class="student-role">Estudiante</div>
                        </div>

                        <div class="col-7 front-info-column">
                            <div class="student-name">{{ $student->nombre_completo }}</div>

                            <div class="student-data-box">
                                <span class="student-data-label">Matrícula</span>
                                <span class="student-data-value font-monospace">{{ $student->matricula }}</span>
                            </div>

                            <div class="student-data-box">
                                <span class="student-data-label">Grupo</span>
                                <span class="student-data-value group-name">
                                    {{ $student->grupo->nombre_grupo ?: $student->grupo->nombre_credencial }}
                                </span>
                            </div>

                            <span class="shift-badge">Matutino</span>
                        </div>
                    </div>

                    {{-- FILA 2: QR a todo el ancho --}}
                    <div class="row g-0 qr-row">
                        <div class="col-12 text-center">
                            <div class="qr-area">
                                <div class="qr-box">
                                    {!! $qrCodeSvg !!}
                                </div>
                                <div class="qr-caption">Acceso y registro de asistencia</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA 2: REVERSO -->
        <div class="card-print-page card-print-page-back">
            <div class="card-side-label no-print">
                <i class="bi bi-card-text me-1"></i> Reverso (5.4 cm × 8.6 cm)
            </div>

            <div class="id-card id-card-back">
                <div class="top-stripe-guinda"></div>
                <div class="top-stripe-gold"></div>

                <div class="id-card-header py-1">
                    <div class="text-uppercase header-school-name fw-bold" style="font-size: 0.52rem;">
                        Escuela Preparatoria Oficial No. 112
                    </div>
                    <div class="font-monospace credential-cct">
                        CCT: 15EBH0214P | Matutino
                    </div>
                    <div class="header-title" style="font-size: 0.54rem;">Datos Institucionales</div>
                </div>

                <div class="header-divider-gold"></div>

                <div class="back-body">
                    <div class="seal-title">Sello de la Institución</div>

                    <div class="school-seal-frame">
                        <img
                            src="{{ asset('images/sello.png') }}"
                            alt="Sello institucional de la Escuela Preparatoria Oficial No. 112"
                            class="school-seal-image"
                        >
                    </div>

                    <div class="director-block">
                        <div class="director-signature-frame">
                            <img
                                src="{{ asset('images/firma.png') }}"
                                alt="Firma del Director Efraín Consuelo Gómez"
                                class="director-signature-image"
                            >
                        </div>
                        <div class="director-line"></div>
                        <div class="director-label">Director</div>
                        <div class="director-name">Efraín Consuelo Gómez</div>
                    </div>
                </div>

                <div class="credential-footer">
                    Vigencia: Ciclo Escolar {{ $student->grupo->ciclo_escolar }}
                </div>
            </div>
        </div>

    </div>

</body>
</html>
