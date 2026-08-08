@extends('layouts.guest')

@section('content')
    <h4 class="fw-bold text-center mb-3" style="color: #8B1B3D; letter-spacing: -0.3px;">Recuperar Contraseña</h4>

    <p class="small text-muted mb-4 text-center">
        ¿Olvidaste tu contraseña? Ingresa tu correo electrónico y te enviaremos un enlace para restablecerla.
    </p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label small">Correo electrónico</label>
            <input id="email" type="email" name="email" class="form-control py-2 @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus style="border-radius: 8px;">
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-wine w-100 py-2.5 text-uppercase fw-bold mb-3" style="font-size: 0.95rem; letter-spacing: 0.5px;">
            Enviar Enlace de Restablecimiento
        </button>
    </form>

    <div class="text-center pt-3 border-top mt-2">
        <a href="{{ route('login') }}" class="small text-decoration-none" style="color: #8B1B3D;">
            <i class="bi bi-arrow-left me-1"></i> Volver a Iniciar Sesión
        </a>
    </div>
@endsection
