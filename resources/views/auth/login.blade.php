@extends('layouts.guest')

@section('content')
    <!-- Session Status -->
    @if(session('status'))
        <div class="alert alert-success small mb-3">
            {{ session('status') }}
        </div>
    @endif

    <h4 class="fw-bold text-center mb-4" style="color: #8B1B3D; letter-spacing: -0.3px;">Iniciar sesión</h4>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label small">Correo electrónico</label>
            <input id="email" type="email" name="email" class="form-control py-2 @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus autocomplete="username" style="border-radius: 8px;">
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label small">Contraseña</label>
            <input id="password" type="password" name="password" class="form-control py-2 @error('password') is-invalid @enderror" required autocomplete="current-password" style="border-radius: 8px;">
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="mt-4 mb-2">
            <button type="submit" class="btn btn-wine w-100 py-2.5 text-uppercase fw-bold" style="font-size: 0.95rem; letter-spacing: 0.5px;">
                INICIAR SESIÓN
            </button>
        </div>

        <!-- Forgot Password Link -->
        @if (Route::has('password.request'))
            <div class="text-end mb-4">
                <a class="small fst-italic text-decoration-none" style="color: #8B1B3D;" href="{{ route('password.request') }}">
                    Recuperar contraseña
                </a>
            </div>
        @endif
    </form>

    <!-- Register section -->
    <div class="text-center pt-3 mt-2">
        <p class="mb-3 small fw-medium text-dark">¿Aún no tienes una cuenta?</p>
        <a href="{{ route('register') }}" class="btn btn-wine px-4 py-2 small text-white text-decoration-none d-inline-block">
            Regístrate
        </a>
    </div>
@endsection
