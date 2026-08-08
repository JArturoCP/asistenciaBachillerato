@extends('layouts.app')

@section('header')
    <h2 class="h4 font-weight-bold text-dark mb-0">
        <i class="bi bi-person-circle text-primary me-2"></i> {{ __('Mi Perfil de Usuario') }}
    </h2>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-md-12">
            <div class="card card-custom p-4 bg-white mb-4">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card card-custom p-4 bg-white mb-4">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card card-custom p-4 bg-white mb-4">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
@endsection
