@extends('layouts.guest')

@section('content')
    <h4 class="fw-bold text-center mb-4" style="color: #8B1B3D; letter-spacing: -0.3px;">Restablecer Contraseña</h4>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label small">Correo Electrónico</label>
            <input id="email" type="email" name="email" class="form-control py-2 @error('email') is-invalid @enderror" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" style="border-radius: 8px;">
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label small">Nueva Contraseña</label>
            <input id="password" type="password" name="password" class="form-control py-2 @error('password') is-invalid @enderror" required autocomplete="new-password" style="border-radius: 8px;">
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label small">Confirmar Contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control py-2 @error('password_confirmation') is-invalid @enderror" required autocomplete="new-password" style="border-radius: 8px;">
            @error('password_confirmation')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-wine w-100 py-2.5 text-uppercase fw-bold mb-3" style="font-size: 0.95rem; letter-spacing: 0.5px;">
            Restablecer Contraseña
        </button>
    </form>
@endsection
