<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Estación de Escaneo Kiosco - Control de Asistencia</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- HTML5 QR Code Library -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .kiosk-header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .kiosk-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .camera-container {
            width: 100%;
            max-width: 450px;
            height: 320px;
            border-radius: 16px;
            overflow: hidden;
            border: 2px dashed rgba(245, 158, 11, 0.5);
            background: #000;
            margin: 0 auto;
            position: relative;
        }

        #reader {
            width: 100% !important;
            height: 100% !important;
            border: none !important;
        }

        #reader video {
            object-fit: cover !important;
            border-radius: 14px;
        }

        .status-display {
            min-height: 340px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
            border-radius: 16px;
            padding: 24px;
        }

        .status-waiting {
            background: rgba(255, 255, 255, 0.03);
            border: 2px dashed rgba(255, 255, 255, 0.15);
        }

        .status-success {
            background: rgba(16, 185, 129, 0.15);
            border: 2px solid #10b981;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
        }

        .status-warning {
            background: rgba(245, 158, 11, 0.15);
            border: 2px solid #f59e0b;
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.3);
        }

        .status-info {
            background: rgba(59, 130, 246, 0.15);
            border: 2px solid #3b82f6;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.3);
        }

        .status-error {
            background: rgba(239, 68, 68, 0.15);
            border: 2px solid #ef4444;
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.3);
        }

        .clock-display {
            font-family: 'Outfit', monospace;
            font-size: 2.2rem;
            font-weight: 700;
            color: #f59e0b;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>

    <!-- Kiosk Top Header -->
    <header class="kiosk-header py-3 px-4 mb-4">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm me-3" title="Volver al Dashboard">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <i class="bi bi-qr-code-scan text-warning fs-3 me-2"></i>
                <div>
                    <h5 class="fw-bold mb-0 text-white">Estación de Registro Escolar</h5>
                    <small class="text-white-50">Escaneo de Credencial Estudiantil (Cámara Web / Lector USB)</small>
                </div>
            </div>

            <div class="text-end">
                <div id="liveClock" class="clock-display">00:00:00 AM</div>
                <div id="liveDate" class="text-white-50 small">Cargando fecha...</div>
            </div>
        </div>
    </header>

    <!-- Main Kiosk Body -->
    <div class="container-fluid px-4">
        <div class="row g-4">
            <!-- Left Side: Scanner Inputs (Webcam + Hidden USB HID) -->
            <div class="col-lg-5">
                <div class="kiosk-card p-4 h-100 text-center">
                    <h6 class="fw-bold text-uppercase tracking-wider mb-3 text-warning">
                        <i class="bi bi-camera-video-fill me-2"></i> Punto de Escaneo Activo
                    </h6>

                    <!-- Webcam Scanner Frame -->
                    <div class="camera-container mb-3">
                        <div id="reader"></div>
                    </div>

                    <!-- Mode Selectors & Controls -->
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <button id="btnToggleCam" onclick="toggleCamera()" class="btn btn-warning btn-sm fw-semibold">
                            <i class="bi bi-camera-fill me-1"></i> Reiniciar Cámara
                        </button>
                    </div>

                    <div class="alert alert-dark border border-secondary text-start small mb-0">
                        <i class="bi bi-keyboard-fill text-info me-2"></i>
                        <strong>Lector USB Conectado</strong>: La estación recibe lecturas de escáner USB tipo pistola de forma transparente sin presionar clic.
                    </div>

                    <!-- Hidden Input for USB Barcode Reader Focus -->
                    <input type="text" id="usbScannerInput" autocomplete="off" style="opacity: 0; position: absolute; left: -9999px;">
                </div>
            </div>

            <!-- Right Side: Status Feedback Display -->
            <div class="col-lg-7">
                <div class="kiosk-card p-4 h-100">
                    <h6 class="fw-bold text-uppercase tracking-wider mb-3 text-warning text-center">
                        <i class="bi bi-bell-fill me-2"></i> Estado de Registro
                    </h6>

                    <div id="statusDisplay" class="status-display status-waiting text-center">
                        <div id="statusIcon" class="mb-3">
                            <i class="bi bi-qr-code text-white-50 display-1"></i>
                        </div>
                        <h3 id="statusTitle" class="fw-bold text-white mb-2">Esperando Escaneo</h3>
                        <p id="statusMessage" class="text-white-50 mb-4">Acerque el código QR de la credencial a la cámara o pase la credencial por el lector USB.</p>

                        <!-- Student Data Box (Hidden initially) -->
                        <div id="studentDetailsBox" class="d-none w-100 bg-dark bg-opacity-50 rounded-4 p-3 border border-secondary">
                            <div class="d-flex align-items-center justify-content-center gap-3">
                                <div class="rounded-circle bg-primary bg-opacity-20 text-primary p-3" style="font-size: 2.5rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="text-start">
                                    <h4 id="studentName" class="fw-bold text-white mb-1">Nombre Alumno</h4>
                                    <div class="text-white-50">Matrícula: <strong id="studentMatricula" class="text-warning font-monospace">BAC-2026-000</strong></div>
                                    <div>Grupo: <span id="studentGroup" class="badge bg-primary px-3">1A-MAT</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript & Sound Logic -->
    <script>
        let html5QrCode = null;
        let isProcessing = false;
        let resetTimeout = null;

        // 1. Audio Synthesizer via Web Audio API
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

        function playSound(type) {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            if (type === 'success') { // High tone single beep
                osc.frequency.value = 880; // A5
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.15);
            } else if (type === 'warning') { // Double tone beep
                osc.frequency.setValueAtTime(660, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.12);
                setTimeout(() => {
                    const osc2 = audioCtx.createOscillator();
                    const gain2 = audioCtx.createGain();
                    osc2.connect(gain2);
                    gain2.connect(audioCtx.destination);
                    osc2.frequency.value = 880;
                    gain2.gain.setValueAtTime(0.3, audioCtx.currentTime);
                    osc2.start();
                    osc2.stop(audioCtx.currentTime + 0.15);
                }, 140);
            } else if (type === 'error') { // Low buzzer
                osc.type = 'sawtooth';
                osc.frequency.value = 220; // A3
                gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.35);
            }
        }

        // 2. Real-time Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-MX', { hour12: true });
            const dateString = now.toLocaleDateString('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            document.getElementById('liveClock').textContent = timeString;
            document.getElementById('liveDate').textContent = dateString.charAt(0).toUpperCase() + dateString.slice(1);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 3. HTML5 QR Code Camera Scanner
        function initCamera() {
            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    const cameraId = devices[0].id;
                    html5QrCode.start(cameraId, config, onScanSuccess);
                }
            }).catch(err => {
                console.warn("No se pudo iniciar cámara:", err);
            });
        }

        function toggleCamera() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => initCamera());
            } else {
                initCamera();
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            if (isProcessing) return;
            sendScanRequest(decodedText, 'qr_camera');
        }

        // 4. USB HID Barcode Reader Buffer Listener
        let usbBuffer = '';
        let lastKeyTime = Date.now();

        document.addEventListener('keydown', (e) => {
            const currentTime = Date.now();

            // If time between keypresses is short, it's a barcode scanner
            if (currentTime - lastKeyTime > 100) {
                usbBuffer = '';
            }
            lastKeyTime = currentTime;

            if (e.key === 'Enter') {
                if (usbBuffer.length > 3 && !isProcessing) {
                    sendScanRequest(usbBuffer, 'qr_usb');
                    usbBuffer = '';
                }
            } else if (e.key.length === 1) {
                usbBuffer += e.key;
            }
        });

        // 5. Send Scan Request to Laravel API
        function sendScanRequest(qrCode, method) {
            isProcessing = true;
            clearTimeout(resetTimeout);

            fetch('/scan/process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    qr_code: qrCode,
                    scan_method: method
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                displayFeedback(body);
            })
            .catch(err => {
                displayFeedback({
                    status: 'error',
                    title: 'Error de Comunicación',
                    message: 'No se pudo conectar con el servidor de asistencia.'
                });
            })
            .finally(() => {
                resetTimeout = setTimeout(resetStatusDisplay, 3500);
            });
        }

        // 6. UI Feedback Renderer
        function displayFeedback(data) {
            const display = document.getElementById('statusDisplay');
            const icon = document.getElementById('statusIcon');
            const title = document.getElementById('statusTitle');
            const msg = document.getElementById('statusMessage');
            const detailsBox = document.getElementById('studentDetailsBox');

            // Reset classes
            display.className = 'status-display text-center';

            if (data.status === 'success') {
                playSound('success');
                display.classList.add('status-success');
                icon.innerHTML = '<i class="bi bi-check-circle-fill text-success display-1"></i>';
            } else if (data.status === 'warning') {
                playSound('warning');
                display.classList.add('status-warning');
                icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-warning display-1"></i>';
            } else if (data.status === 'info') {
                playSound('success');
                display.classList.add('status-info');
                icon.innerHTML = '<i class="bi bi-box-arrow-right text-info display-1"></i>';
            } else {
                playSound('error');
                display.classList.add('status-error');
                icon.innerHTML = '<i class="bi bi-x-circle-fill text-danger display-1"></i>';
            }

            title.textContent = data.title || 'Resultado Escaneo';
            msg.textContent = data.message || '';

            if (data.student) {
                document.getElementById('studentName').textContent = data.student.name;
                document.getElementById('studentMatricula').textContent = data.student.matricula;
                document.getElementById('studentGroup').textContent = 'Grupo ' + data.student.group;
                detailsBox.classList.remove('d-none');
            } else {
                detailsBox.classList.add('d-none');
            }
        }

        function resetStatusDisplay() {
            const display = document.getElementById('statusDisplay');
            const icon = document.getElementById('statusIcon');
            const title = document.getElementById('statusTitle');
            const msg = document.getElementById('statusMessage');
            const detailsBox = document.getElementById('studentDetailsBox');

            display.className = 'status-display status-waiting text-center';
            icon.innerHTML = '<i class="bi bi-qr-code text-white-50 display-1"></i>';
            title.textContent = 'Esperando Escaneo';
            msg.textContent = 'Acerque el código QR de la credencial a la cámara o pase la credencial por el lector USB.';
            detailsBox.classList.add('d-none');
            isProcessing = false;
        }

        // Initialize camera on load
        window.addEventListener('load', () => {
            initCamera();
        });
    </script>
</body>
</html>
