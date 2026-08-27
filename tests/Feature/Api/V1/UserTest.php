<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list users with pagination', function () {
    $authUser = User::factory()->create();
    User::factory()->count(20)->create();

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', ['per_page' => 10, 'page' => 1]));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Users retrieved successfully.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ],
            'meta' => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                    'has_more_pages',
                ],
            ],
        ]);

    expect($response->json('meta.pagination.per_page'))->toBe(10)
        ->and($response->json('meta.pagination.count'))->toBe(10);
});

test('user list can be filtered by name and email', function () {
    $authUser = User::factory()->create();
    $targetUser = User::factory()->create([
        'name' => 'Alice Unique',
        'email' => 'alice.unique@example.com',
    ]);
    User::factory()->create([
        'name' => 'Bob Ordinary',
        'email' => 'bob.ordinary@example.com',
    ]);

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', [
            'filter' => [
                'email' => 'alice.unique@example.com',
            ],
        ]));

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['email'])->toBe('alice.unique@example.com');
});

test('user list can be searched by keyword', function () {
    $authUser = User::factory()->create();
    User::factory()->create(['name' => 'Alexander Hamilton', 'email' => 'alex@example.com']);
    User::factory()->create(['name' => 'George Washington', 'email' => 'george@example.com']);

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', ['search' => 'Hamilton']));

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Alexander Hamilton');
});

test('user list can be sorted', function () {
    $authUser = User::factory()->create(['name' => 'MMM AuthUser']);
    User::factory()->create(['name' => 'AAA User', 'email' => 'aaa@example.com']);
    User::factory()->create(['name' => 'ZZZ User', 'email' => 'zzz@example.com']);

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', ['sort' => 'name']));

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data[0]['name'])->toBe('AAA User');

    $responseDesc = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', ['sort' => '-name']));

    $responseDesc->assertStatus(200);
    $dataDesc = $responseDesc->json('data');

    expect($dataDesc[0]['name'])->toBe('ZZZ User');
});

test('user list supports cursor pagination', function () {
    $authUser = User::factory()->create();
    User::factory()->count(10)->create();

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', ['cursor' => 'first', 'per_page' => 5]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'meta' => [
                'pagination' => [
                    'has_more_pages',
                    'next_cursor',
                    'prev_cursor',
                ],
            ],
        ]);
});

test('user can be created via API', function () {
    $authUser = User::factory()->create();

    $response = $this->actingAs($authUser, 'sanctum')
        ->postJson(route('api.v1.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password@123',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => [
                'name' => 'New User',
                'email' => 'newuser@example.com',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
    ]);
});

test('user can be retrieved by id', function () {
    $authUser = User::factory()->create();
    $targetUser = User::factory()->create(['name' => 'Target User']);

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.show', $targetUser));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $targetUser->id,
                'name' => 'Target User',
            ],
        ]);
});

test('single user can be retrieved with dynamic includes, fields, and appends via apiQuery', function () {
    $authUser = User::factory()->create();
    $targetUser = User::factory()->create(['name' => 'John Wick']);
    $targetUser->createToken('Assassins Token');

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.show', [
            'user' => $targetUser,
            'include' => 'tokens',
            'append' => 'initials,latest_token_name',
            'fields' => [
                'users' => ['id', 'name', 'email'],
                'tokens' => ['id', 'name'],
            ],
        ]));

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveKey('initials', 'JW')
        ->and($data)->toHaveKey('latest_token_name', 'Assassins Token')
        ->and($data)->toHaveKey('tokens')
        ->and($data['tokens'][0])->toHaveKey('name', 'Assassins Token')
        ->and($data['tokens'][0])->not->toHaveKey('abilities');
});

test('user can be updated via API', function () {
    $authUser = User::factory()->create();
    $targetUser = User::factory()->create(['name' => 'Original Name']);

    $response = $this->actingAs($authUser, 'sanctum')
        ->putJson(route('api.v1.users.update', $targetUser), [
            'name' => 'Modified Name',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => [
                'name' => 'Modified Name',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $targetUser->id,
        'name' => 'Modified Name',
    ]);
});

test('user can be deleted via API', function () {
    $authUser = User::factory()->create();
    $targetUser = User::factory()->create();

    $response = $this->actingAs($authUser, 'sanctum')
        ->deleteJson(route('api.v1.users.destroy', $targetUser));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);

    $this->assertDatabaseMissing('users', [
        'id' => $targetUser->id,
    ]);
});

test('user list can dynamically append computed attributes', function () {
    $authUser = User::factory()->create([
        'name' => 'Sarah Connor',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', [
            'append' => 'initials,is_verified',
            'filter' => ['email' => $authUser->email],
        ]));

    $response->assertStatus(200);
    $data = $response->json('data.0');

    expect($data)->toHaveKey('initials', 'SC')
        ->and($data)->toHaveKey('is_verified', true);
});

test('relation-based append computes value and does not leak raw relation data in response', function () {
    $authUser = User::factory()->create(['name' => 'Token Master']);
    $authUser->createToken('MacBook Token');
    $authUser->createToken('iPhone Token');

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', [
            'append' => 'latest_token_name',
            'filter' => ['email' => $authUser->email],
        ]));

    $response->assertStatus(200);
    $data = $response->json('data.0');

    // 1. Computed attribute is present
    expect($data)->toHaveKey('latest_token_name', 'iPhone Token');

    // 2. Raw relation array is NOT leaked because ?include=tokens was not requested
    expect($data)->not->toHaveKey('tokens');
});

test('relation-based append executes zero N+1 queries across multiple models', function () {
    $authUser = User::factory()->create();
    $users = User::factory()->count(10)->create();
    foreach ($users as $u) {
        $u->createToken('Token A');
        $u->createToken('Token B');
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', [
            'append' => 'latest_token_name',
            'per_page' => 15,
        ]));

    $response->assertStatus(200);
    $queries = DB::getQueryLog();

    // Queries: 1 count query + 1 users select query + 1 tokens eager load query = 3 queries total for all 11 users!
    expect(count($queries))->toBeLessThanOrEqual(4);
});

test('user list supports relation sparse fieldsets', function () {
    $authUser = User::factory()->create();
    $authUser->createToken('Desktop Token');

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', [
            'include' => 'tokens',
            'fields' => [
                'users' => ['id', 'name', 'email'],
                'tokens' => ['id', 'name'],
            ],
            'filter' => ['email' => $authUser->email],
        ]));

    $response->assertStatus(200);
    $data = $response->json('data.0');

    expect($data)->toHaveKey('tokens');
    $token = $data['tokens'][0];
    expect($token)->toHaveKey('id')
        ->and($token)->toHaveKey('name', 'Desktop Token')
        ->and($token)->not->toHaveKey('abilities');
});

test('default appends on model are automatically attached in resource response without query param', function () {
    $authUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', [
            'filter' => ['email' => $authUser->email],
        ]));

    $response->assertStatus(200);
    $data = $response->json('data.0');

    // 'is_verified' is in User::$appends, so it must be automatically attached
    expect($data)->toHaveKey('is_verified', true);
});

test('hidden fields like password cannot be queried via sparse fields or leaked in response', function () {
    $authUser = User::factory()->create();

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', [
            'fields' => 'id,name,password,remember_token',
            'filter' => ['email' => $authUser->email],
        ]));

    $response->assertStatus(200);
    $data = $response->json('data.0');

    expect($data)->not->toHaveKey('password')
        ->and($data)->not->toHaveKey('remember_token');
});

test('user list supports limit=-1 and per_page=-1 to fetch all records without pagination splits', function () {
    $authUser = User::factory()->create();
    User::factory()->count(25)->create();

    $response = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', ['limit' => -1]));

    $response->assertStatus(200);
    $data = $response->json('data');
    $meta = $response->json('meta.pagination');

    expect($data)->toHaveCount(26)
        ->and($meta['total'])->toBe(26)
        ->and($meta['count'])->toBe(26)
        ->and($meta['has_more_pages'])->toBeFalse();
});

test('scramble api documentation endpoints are accessible under developer prefix', function () {
    $docsResponse = $this->get('/developer/docs/api');
    $docsResponse->assertStatus(200);

    $jsonResponse = $this->get('/developer/docs/api.json');
    $jsonResponse->assertStatus(200)
        ->assertJsonStructure([
            'openapi',
            'info',
            'paths',
        ]);
});

test('meta key is exclusively attached to paginated responses and omitted from single resources', function () {
    $authUser = User::factory()->create();

    // 1. Paginated Index endpoint HAS meta
    $indexResponse = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index'));

    $indexResponse->assertStatus(200)
        ->assertJsonStructure(['success', 'message', 'data', 'meta' => ['pagination']]);

    expect($indexResponse->json())->toHaveKey('meta');

    // 2. Single Resource Show endpoint DOES NOT have meta
    $showResponse = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.show', ['user' => $authUser->id]));

    $showResponse->assertStatus(200);
    expect($showResponse->json())->not->toHaveKey('meta');

    // 3. Auth Me endpoint DOES NOT have meta
    $meResponse = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.auth.me'));

    $meResponse->assertStatus(200);
    expect($meResponse->json())->not->toHaveKey('meta');
});
