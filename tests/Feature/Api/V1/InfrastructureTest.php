<?php

test('health endpoint returns 200 healthy with database, cache, and storage status', function () {
    $response = $this->getJson(route('api.v1.health'));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'status' => 'healthy',
        ])
        ->assertJsonStructure([
            'success',
            'status',
            'timestamp',
            'environment',
            'checks' => [
                'database' => ['status', 'latency_ms', 'connection'],
                'cache' => ['status', 'store'],
                'storage' => ['status', 'disk'],
            ],
        ]);
});

test('assign request id middleware attaches x-request-id header to response', function () {
    // 1. When no incoming X-Request-Id is passed, backend generates one
    $response = $this->getJson(route('api.v1.health'));

    $response->assertHeader('X-Request-Id');
    $generatedId = $response->headers->get('X-Request-Id');
    expect($generatedId)->not->toBeEmpty();

    // 2. When incoming X-Request-Id is provided, backend preserves it
    $customId = 'custom-trace-uuid-12345';
    $customResponse = $this->withHeaders(['X-Request-Id' => $customId])
        ->getJson(route('api.v1.health'));

    $customResponse->assertHeader('X-Request-Id', $customId);
});

test('security headers middleware attaches production hardening headers', function () {
    $response = $this->getJson(route('api.v1.health'));

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-XSS-Protection', '1; mode=block')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('auth rate limiter throttles excessive login requests', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson(route('api.v1.auth.login'), [
            'email' => 'invalid@example.com',
            'password' => 'wrongpass',
        ]);
    }

    // 11th request triggers throttle
    $throttledResponse = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'invalid@example.com',
        'password' => 'wrongpass',
    ]);

    $throttledResponse->assertStatus(429)
        ->assertJson([
            'success' => false,
            'message' => 'Too many requests. Please slow down and try again later.',
        ]);
});
