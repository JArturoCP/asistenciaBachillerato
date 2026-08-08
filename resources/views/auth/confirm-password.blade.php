@extends('layouts.guest')

@section('content')
    <div class="mb-4 small text-muted">
        {{ __('Esta es un área segura del sistema. Por favor confirma tu contraseña antes de continuar.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label small">Contraseña</label>
            <input id="password" type="password" name="password" class="form-control py-2 @error('password') is-invalid @enderror" required autocomplete="current-password" style="border-radius: 8px;">
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-wine w-100 py-2.5 text-uppercase fw-bold" style="font-size: 0.95rem; letter-spacing: 0.5px;">
            Confirmar
        </button>
    </form>
@endsection
