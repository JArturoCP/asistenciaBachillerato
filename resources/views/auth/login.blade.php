<x-guest-layout>
    <!-- Session Status -->
    @if(session('status'))
        <div class="alert alert-success small mb-3">
            {{ session('status') }}
        </div>
    @endif

    <h5 class="fw-bold mb-3 text-dark text-center"><i class="bi bi-box-arrow-in-right me-2 text-primary"></i> Iniciar Sesión</h5>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold small text-muted">Correo Electrónico</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="usuario@escuela.edu.mx">
            </div>
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold small text-muted">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password" placeholder="••••••••">
            </div>
            @error('password')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <label for="remember_me" class="form-check-label small text-muted">Recordarme</label>
            </div>
            @if (Route::has('password.request'))
                <a class="small text-decoration-none text-primary" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mb-3">
            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
        </button>
    </form>

    <hr class="my-3">

    <!-- Registration Link -->
    <div class="text-center pt-2">
        <span class="small text-muted">¿Aún no tienes una cuenta registrada?</span><br>
        <a href="{{ route('register') }}" class="btn btn-outline-success btn-sm w-100 mt-2 fw-semibold">
            <i class="bi bi-person-plus-fill me-1"></i> Crear Nueva Cuenta / Registrarse
        </a>
    </div>
</x-guest-layout>
