<?php

test('registration screen redirects to login (registration disabled)', function () {
    $response = $this->get('/register');

    $response->assertRedirect(route('login'));
});

test('new users cannot register (registration disabled)', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});
