<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register and stay pending admin approval', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test_register@example.com',
        'role' => 'parent',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login', absolute: false));
    $response->assertSessionHas('status');
});
