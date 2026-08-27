<?php

use Illuminate\Support\Facades\Gate;

test('unauthenticated visitor accessing developer root is redirected to login', function () {
    $response = $this->get('/developer');

    $response->assertRedirect('/developer/login');
});

test('unauthenticated visitor accessing dashboard is redirected to login', function () {
    $response = $this->get(route('developer.dashboard'));

    $response->assertRedirect(route('developer.login'));
});

test('developer login fails with invalid credentials', function () {
    $response = $this->post(route('developer.login.submit'), [
        'username' => 'wrong_user',
        'password' => 'wrong_password',
    ]);

    $response->assertSessionHasErrors('message')
        ->assertRedirect();

    expect(session()->get('developer_authenticated'))->toBeNull();
});

test('developer login succeeds with configured environment credentials', function () {
    config([
        'developer.username' => 'superdev',
        'developer.password' => 'supersecretpass',
    ]);

    $response = $this->post(route('developer.login.submit'), [
        'username' => 'superdev',
        'password' => 'supersecretpass',
    ]);

    $response->assertRedirect(route('developer.dashboard'));
    expect(session()->get('developer_authenticated'))->toBeTrue();
});

test('authenticated developer can access dashboard and view developer suite cards', function () {
    $response = $this->withSession(['developer_authenticated' => true])
        ->get(route('developer.dashboard'));

    $response->assertStatus(200)
        ->assertSee('Developer Suites', false)
        ->assertSee('Laravel Telescope')
        ->assertSee('Laravel Horizon')
        ->assertSee('Log Viewer')
        ->assertSee('API Documentation');
});

test('developer logout clears session and redirects to login', function () {
    $response = $this->withSession(['developer_authenticated' => true])
        ->post(route('developer.logout'));

    $response->assertRedirect(route('developer.login'));
    expect(session()->get('developer_authenticated'))->toBeNull();
});

test('developer session unlocks internal authorization gates', function () {
    $this->withSession(['developer_authenticated' => true]);

    expect(Gate::check('viewTelescope'))->toBeTrue()
        ->and(Gate::check('viewHorizon'))->toBeTrue()
        ->and(Gate::check('viewLogViewer'))->toBeTrue()
        ->and(Gate::check('viewApiDocs'))->toBeTrue();
});
