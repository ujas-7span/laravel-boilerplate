<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invalid datetime or date filter coerces safely and returns empty result', function () {
    $authUser = User::factory()->create();
    User::factory()->count(3)->create();

    // 1. Invalid date on exact equality
    $response = $this->actingAs($authUser, 'sanctum')->getJson(route('api.v1.users.index', [
        'filter' => [
            'email_verified_at' => 'not-a-valid-date-format',
        ],
    ]));

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');

    // 2. Invalid date on _after / _from range
    $responseAfter = $this->actingAs($authUser, 'sanctum')->getJson(route('api.v1.users.index', [
        'filter' => [
            'created_at_after' => 'invalid-date-string-123',
        ],
    ]));

    $responseAfter->assertStatus(200)
        ->assertJsonCount(0, 'data');

    // 3. Invalid date on _before / _to range
    $responseBefore = $this->actingAs($authUser, 'sanctum')->getJson(route('api.v1.users.index', [
        'filter' => [
            'created_at_before' => 'invalid-date-string-456',
        ],
    ]));

    $responseBefore->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

test('valid date range filters return expected records', function () {
    $authUser = User::factory()->create();
    $pastUser = User::factory()->create(['created_at' => now()->subDays(10)]);
    $futureUser = User::factory()->create(['created_at' => now()->addDays(5)]);

    $response = $this->actingAs($authUser, 'sanctum')->getJson(route('api.v1.users.index', [
        'filter' => [
            'created_at_after' => now()->subDay()->toDateTimeString(),
        ],
    ]));

    $response->assertStatus(200);
    $dataIds = collect($response->json('data'))->pluck('id')->all();

    expect($dataIds)->toContain($futureUser->id)
        ->and($dataIds)->not->toContain($pastUser->id);
});
