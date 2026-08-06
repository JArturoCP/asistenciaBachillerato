<?php

it('redirects root to login page', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});
