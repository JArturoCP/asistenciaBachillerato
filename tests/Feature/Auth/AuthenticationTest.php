<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create(['is_approved' => true]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password and receives password error', function () {
    $user = User::factory()->create(['is_approved' => true]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['password']);
});

test('users can not authenticate with unregistered email and receives email error', function () {
    $response = $this->post('/login', [
        'email' => 'unregistered.user@escuela.edu.mx',
        'password' => 'somepassword',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email']);
});

test('users can logout', function () {
    $user = User::factory()->create(['is_approved' => true]);

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
