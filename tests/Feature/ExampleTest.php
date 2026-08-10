<?php

it('redirects guest at root to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

it('redirects logged in user at root to dashboard', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('dashboard'));
});
