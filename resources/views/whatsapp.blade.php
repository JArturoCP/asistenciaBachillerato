@extends('layouts.app')

@section('header')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-whatsapp text-success me-2"></i>WhatsApp</h1>
        <div class="text-muted small">Vinculación y prueba del canal de notificaciones de asistenciaBachillerato</div>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver al dashboard
    </a>
</div>
@endsection

@section('content')
@php
    $connected = (bool) ($status['connected'] ?? false);
    $serviceOk = (bool) ($status['ok'] ?? false);
    $connectionStatus = $status['status'] ?? 'unknown';
    $number = $status['number'] ?? null;
    $connectedAt = $status['connectedAt'] ?? null;
@endphp

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-custom h-100 border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <div class="text-uppercase small fw-semibold text-muted">Estado del canal</div>
                        <h2 class="h5 mb-0">Baileys / WhatsApp</h2>
                    </div>
                    <span id="connectionBadge" class="badge rounded-pill {{ $connected ? 'bg-success' : ($serviceOk ? 'bg-warning text-dark' : 'bg-danger') }} px-3 py-2">
                        @if($connected)
                            <i class="bi bi-check-circle-fill me-1"></i> Conectado
                        @elseif($serviceOk)
                            <i class="bi bi-qr-code me-1"></i> Pendiente de vincular
                        @else
                            <i class="bi bi-x-circle-fill me-1"></i> Servicio no disponible
                        @endif
                    </span>
                </div>

                <div class="border rounded-3 p-3 bg-light mb-3">
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Microservicio</span>
                        <strong id="serviceStatus">{{ $serviceOk ? 'Activo' : 'Sin conexión' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">WhatsApp</span>
                        <strong id="whatsappStatus">{{ $connected ? 'Conectado' : $connectionStatus }}</strong>
                    </div>
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <span class="text-muted">Número vinculado</span>
                        <strong id="connectedNumber">{{ $number ?: '—' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between gap-3">
                        <span class="text-muted">Última conexión</span>
                        <strong id="connectedAt">{{ $connectedAt ?: '—' }}</strong>
                    </div>
                </div>

                @if(!$serviceOk)
                    <div class="alert alert-danger mb-0">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Baileys no está disponible</div>
                        <div class="small">{{ $status['message'] ?? 'Inicie el microservicio de WhatsApp y verifique las variables de entorno.' }}</div>
                    </div>
                @elseif($connected)
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        El dispositivo está vinculado y asistenciaBachillerato puede enviar notificaciones.
                    </div>

                    <form method="POST" action="{{ route('admin.whatsapp.logout') }}" onsubmit="return confirm('¿Desea desvincular la sesión de WhatsApp? Será necesario escanear un nuevo QR.');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-box-arrow-right me-1"></i> Desvincular WhatsApp
                        </button>
                    </form>
                @else
                    <div class="text-center" id="qrContainer">
                        <p class="text-muted mb-3">
                            En el teléfono abra <strong>WhatsApp → Dispositivos vinculados → Vincular dispositivo</strong> y escanee el código.
                        </p>

                        @if($qr)
                            <div class="bg-white border rounded-4 p-3 d-inline-block shadow-sm mb-3">
                                <img id="qrImage" src="{{ $qr }}" alt="Código QR para vincular WhatsApp" class="img-fluid" style="width: 290px; max-width: 100%;">
                            </div>
                        @else
                            <div class="alert alert-warning" id="qrWaitingMessage">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ $qrMessage ?: 'Esperando un nuevo código QR...' }}
                            </div>
                            <img id="qrImage" src="" alt="Código QR para vincular WhatsApp" class="img-fluid d-none" style="width: 290px; max-width: 100%;">
                        @endif

                        <div class="small text-muted">
                            El estado se actualiza automáticamente mientras esta pantalla permanezca abierta.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card card-custom border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-send-fill fs-5"></i>
                    </div>
                    <div>
                        <h2 class="h5 mb-1">Mensaje de prueba</h2>
                        <div class="text-muted small">Compruebe el envío antes de habilitar el canal en operación.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.whatsapp.send') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold">Teléfono</label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            value="{{ old('phone') }}"
                            class="form-control @error('phone') is-invalid @enderror"
                            placeholder="Ej. 7221234567 o +527221234567"
                            autocomplete="tel"
                        >
                        <div class="form-text">Si captura 10 dígitos, el servicio agregará automáticamente el código de México (+52).</div>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label fw-semibold">Mensaje</label>
                        <textarea
                            name="message"
                            id="message"
                            rows="6"
                            class="form-control @error('message') is-invalid @enderror"
                            placeholder="Escriba un mensaje de prueba..."
                        >{{ old('message', 'Mensaje de prueba enviado desde asistenciaBachillerato.') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success px-4" {{ $connected ? '' : 'disabled' }}>
                        <i class="bi bi-whatsapp me-1"></i> Enviar mensaje
                    </button>

                    @if(!$connected)
                        <span class="text-muted small ms-2">Vincule WhatsApp para habilitar el envío.</span>
                    @endif
                </form>
            </div>
        </div>

        <div class="card card-custom border-0 mt-4">
            <div class="card-body p-4">
                <h3 class="h6 mb-3"><i class="bi bi-shield-check me-2"></i>Configuración segura</h3>
                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-1">Laravel se comunica con Baileys mediante una API privada autenticada por token.</li>
                    <li class="mb-1">Las credenciales de sesión se almacenan fuera de Git en <code>whatsapp-service/auth/</code>.</li>
                    <li>El microservicio está pensado para escuchar únicamente en <code>127.0.0.1</code>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@if(!$connected)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const statusUrl = @json(route('admin.whatsapp.status'));
    let previousConnected = false;

    const refreshStatus = async () => {
        try {
            const response = await fetch(statusUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) return;

            const payload = await response.json();
            const freshStatus = payload.status || {};

            if (freshStatus.connected && !previousConnected) {
                window.location.reload();
                return;
            }

            previousConnected = Boolean(freshStatus.connected);

            if (payload.qr) {
                const qrImage = document.getElementById('qrImage');
                const waiting = document.getElementById('qrWaitingMessage');
                if (qrImage) {
                    qrImage.src = payload.qr;
                    qrImage.classList.remove('d-none');
                }
                if (waiting) waiting.classList.add('d-none');
            }
        } catch (error) {
            // La tarjeta principal ya informa el estado inicial. El siguiente ciclo volverá a intentar.
        }
    };

    setInterval(refreshStatus, 5000);
});
</script>
@endif
@endsection
