<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register successfully', function () {
    $payload = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
        'device_name' => 'iPhone',
    ];

    $response = $this->postJson(route('api.v1.auth.register'), $payload);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'User registered successfully.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'token',
                'token_type',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'name' => 'John Doe',
    ]);
});

test('registration fails when email is already taken or password confirmation does not match', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => 'Password@123',
        'password_confirmation' => 'DifferentPassword',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'The given data was invalid.',
        ])
        ->assertJsonValidationErrors(['email', 'password']);
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('Secret123!'),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'login@example.com',
        'password' => 'Secret123!',
        'device_name' => 'MacBook Pro',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Logged in successfully.',
            'data' => [
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ],
        ]);

    expect($response->json('data.token'))->toBeString();
});

test('login fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('Secret123!'),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'user@example.com',
        'password' => 'WrongPassword',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonValidationErrors(['email']);
});

test('authenticated user can get own profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.auth.me'));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User profile retrieved successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
});

test('unauthenticated user cannot access me endpoint', function () {
    $response = $this->getJson(route('api.v1.auth.me'));

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});

test('authenticated user can update profile', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson(route('api.v1.auth.update-me'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('authenticated user can change password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123'),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.auth.change-password'), [
            'current_password' => 'CurrentPassword123',
            'password' => 'NewSecretPassword456',
            'password_confirmation' => 'NewSecretPassword456',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);

    $user->refresh();
    expect(Hash::check('NewSecretPassword456', $user->password))->toBeTrue();
});

test('user cannot change password with invalid current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123'),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.auth.change-password'), [
            'current_password' => 'WrongPassword',
            'password' => 'NewSecretPassword456',
            'password_confirmation' => 'NewSecretPassword456',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);
});

test('user can request forgot password link and receives queued notification', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'resetme@example.com']);

    $response = $this->postJson(route('api.v1.auth.forgot-password'), [
        'email' => 'resetme@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    Notification::assertSentTo(
        $user,
        App\Notifications\Auth\ResetPasswordNotification::class,
        function (App\Notifications\Auth\ResetPasswordNotification $notification) use ($user) {
            $mail = $notification->toMail($user);
            expect($mail->subject)->toContain(config('app.name'));
            expect($notification->token)->toBeString();

            return true;
        }
    );
});

test('user can reset password with valid token', function () {
    $user = User::factory()->create(['email' => 'resetuser@example.com']);
    $token = Password::broker()->createToken($user);

    $response = $this->postJson(route('api.v1.auth.reset-password'), [
        'token' => $token,
        'email' => 'resetuser@example.com',
        'password' => 'BrandNewPassword123',
        'password_confirmation' => 'BrandNewPassword123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $user->refresh();
    expect(Hash::check('BrandNewPassword123', $user->password))->toBeTrue();
});

test('user can logout and revoke current access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test_token');

    $response = $this->withToken($token->plainTextToken)
        ->postJson(route('api.v1.auth.logout'));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);

    expect($user->tokens()->count())->toBe(0);
});

test('user can logout from all devices', function () {
    $user = User::factory()->create();
    $user->createToken('device_1');
    $user->createToken('device_2');
    $activeToken = $user->createToken('device_3');

    expect($user->tokens()->count())->toBe(3);

    $response = $this->withToken($activeToken->plainTextToken)
        ->postJson(route('api.v1.auth.logout-all'));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Logged out from all devices successfully.',
        ]);

    expect($user->tokens()->count())->toBe(0);
});
