<?php

use App\Models\User;

test('default locale is en and response includes Content-Language header', function () {
    $response = $this->getJson(route('api.v1.health'));

    $response->assertStatus(200)
        ->assertHeader('Content-Language', 'en');

    expect(app()->getLocale())->toBe('en');
});

test('locale can be negotiated via Accept-Language and X-Locale headers', function () {
    // 1. Via X-Locale header
    $responseWithHeader = $this->withHeaders(['X-Locale' => 'en'])
        ->getJson(route('api.v1.health'));

    $responseWithHeader->assertStatus(200)
        ->assertHeader('Content-Language', 'en');

    // 2. Via Accept-Language header
    $responseWithAccept = $this->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
        ->getJson(route('api.v1.health'));

    $responseWithAccept->assertStatus(200)
        ->assertHeader('Content-Language', 'en');

    // 3. Via query parameter ?locale=en
    $responseWithQuery = $this->getJson(route('api.v1.health', ['locale' => 'en']));

    $responseWithQuery->assertStatus(200)
        ->assertHeader('Content-Language', 'en');
});

test('unsupported locale gracefully falls back to default locale en', function () {
    $response = $this->withHeaders(['X-Locale' => 'unsupported_lang'])
        ->getJson(route('api.v1.health'));

    $response->assertStatus(200)
        ->assertHeader('Content-Language', 'en');

    expect(app()->getLocale())->toBe('en');
});

test('api endpoints return localized message strings from lang messages file', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.auth.me'));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => __('message.auth.profile_retrieved'),
        ]);
});
