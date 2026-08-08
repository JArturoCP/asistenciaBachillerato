@extends('layouts.guest')

@section('content')
    <h4 class="fw-bold text-center mb-4" style="color: #8B1B3D; letter-spacing: -0.3px;">Crear Nueva Cuenta</h4>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Nombre(s) -->
        <div class="mb-3">
            <label for="nombre" class="form-label small">Nombre(s)</label>
            <input id="nombre" type="text" name="nombre" class="form-control py-2 @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required autofocus placeholder="Ej. Carlos" style="border-radius: 8px;">
            @error('nombre')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Apellidos -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="apellido_paterno" class="form-label small">Apellido Paterno</label>
                <input id="apellido_paterno" type="text" name="apellido_paterno" class="form-control py-2 @error('apellido_paterno') is-invalid @enderror" value="{{ old('apellido_paterno') }}" required placeholder="Ej. Pérez" style="border-radius: 8px;">
                @error('apellido_paterno')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="apellido_materno" class="form-label small">Apellido Materno</label>
                <input id="apellido_materno" type="text" name="apellido_materno" class="form-control py-2 @error('apellido_materno') is-invalid @enderror" value="{{ old('apellido_materno') }}" placeholder="Ej. Hernández" style="border-radius: 8px;">
                @error('apellido_materno')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label small">Correo Electrónico</label>
            <input id="email" type="email" name="email" class="form-control py-2 @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="tutor@gmail.com" style="border-radius: 8px;">
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Role Selector -->
        <div class="mb-3">
            <label for="role" class="form-label small">Tipo de Cuenta / Rol</label>
            <select id="role" name="role" class="form-select py-2 @error('role') is-invalid @enderror" required style="border-radius: 8px;">
                <option value="parent" {{ old('role') == 'parent' ? 'selected' : '' }}>Padre de Familia / Tutor Legal</option>
                <option value="teacher" {{ old('role') == 'teacher' ? 'selected' : '' }}>Docente / Profesor</option>
            </select>
            @error('role')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Phone Number -->
        <div class="mb-3">
            <label for="phone" class="form-label small">Teléfono de Contacto (Opcional)</label>
            <input id="phone" type="text" name="phone" class="form-control py-2 @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="10 dígitos" style="border-radius: 8px;">
            @error('phone')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label small">Contraseña</label>
            <input id="password" type="password" name="password" class="form-control py-2 @error('password') is-invalid @enderror" required placeholder="Mínimo 8 caracteres" style="border-radius: 8px;">
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label small">Confirmar Contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control py-2" required placeholder="Repetir contraseña" style="border-radius: 8px;">
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-wine w-100 py-2.5 text-uppercase fw-bold mb-3" style="font-size: 0.95rem; letter-spacing: 0.5px;">
            REGISTRAR CUENTA
        </button>
    </form>

    <!-- Login Link -->
    <div class="text-center pt-3 border-top mt-2">
        <p class="mb-3 small fw-medium text-dark">¿Ya tienes una cuenta registrada?</p>
        <a href="{{ route('login') }}" class="btn btn-wine px-4 py-2 small text-white text-decoration-none d-inline-block">
            Iniciar Sesión
        </a>
    </div>
@endsection
