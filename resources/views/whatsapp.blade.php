<!DOCTYPE html>
<html>
<head>
    <title>Prueba de Envío WhatsApp - UltraMsg API</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h4 mb-0">Enviar Mensaje de WhatsApp (UltraMsg)</h2>
                    </div>
                    <div class="card-body">
                        @if ($message = Session::get('success'))
                        <div class="alert alert-success alert-block">
                            <strong>{{ $message }}</strong>
                        </div>
                        @endif
                        @if ($message = Session::get('error'))
                        <div class="alert alert-danger alert-block">
                            <strong>{{ $message }}</strong>
                        </div>
                        @endif
                        <form method="POST" action="{{ url('/whatsapp') }}">
                            {{ csrf_field() }}
                            <div class="mb-3">
                                <label for="phone" class="form-label">Teléfono (con código de país):</label>
                                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="Ej. +5215512345678 o 7226017472">
                                <small class="form-text text-muted">Incluye el código de país o 10 dígitos (ej. +52 para México).</small>
                                @error('phone')
                                <span class="text-danger d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Mensaje:</label>
                                <textarea name="message" id="message" rows="4" class="form-control @error('message') is-invalid @enderror" placeholder="Escribe tu mensaje de prueba aquí..."></textarea>
                                @error('message')
                                <span class="text-danger d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">Enviar Mensaje vía UltraMsg</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>