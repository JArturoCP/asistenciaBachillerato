<x-guest-layout>
    <h5 class="fw-bold mb-3 text-dark text-center"><i class="bi bi-person-plus text-success me-2"></i> Crear Nueva Cuenta</h5>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold small text-muted">Nombre Completo</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus placeholder="Ej. Carlos Pérez Hernández">
            </div>
            @error('name')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold small text-muted">Correo Electrónico</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="tutor@gmail.com">
            </div>
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Role Selector -->
        <div class="mb-3">
            <label for="role" class="form-label fw-semibold small text-muted">Tipo de Cuenta / Rol</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-shield-person"></i></span>
                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                    <option value="parent" {{ old('role') == 'parent' ? 'selected' : '' }}>Padre de Familia / Tutor Legal</option>
                    <option value="teacher" {{ old('role') == 'teacher' ? 'selected' : '' }}>Docente / Profesor</option>
                </select>
            </div>
            @error('role')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Phone Number -->
        <div class="mb-3">
            <label for="phone" class="form-label fw-semibold small text-muted">Teléfono de Contacto (Opcional)</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                <input id="phone" type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="10 dígitos">
            </div>
            @error('phone')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold small text-muted">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required placeholder="Mínimo 8 caracteres">
            </div>
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-semibold small text-muted">Confirmar Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required placeholder="Repetir contraseña">
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-success w-100 fw-bold py-2 mb-3">
            <i class="bi bi-person-check-fill me-1"></i> Registrar Cuenta
        </button>
    </form>

    <hr class="my-3">

    <!-- Login Link -->
    <div class="text-center pt-2">
        <span class="small text-muted">¿Ya tienes una cuenta registrada?</span><br>
        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm w-100 mt-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión aquí
        </a>
    </div>
</x-guest-layout>
