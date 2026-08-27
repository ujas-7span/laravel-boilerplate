<?php

use App\Models\User;
use App\Models\Media;
use App\Models\TempFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

test('authenticated user can generate a pre-signed upload url without mime_type', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.signed-url'), [
            'filename' => 'avatar.png',
            'tag' => 'profile',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Pre-signed upload URL generated successfully.',
        ])
        ->assertJsonStructure([
            'data' => [
                'url',
                'file_url',
                'key',
                'directory',
                'filename',
                'extension',
                'mime_type',
                'tag',
                'disk',
            ],
        ]);

    $data = $response->json('data');
    expect($data['extension'])->toBe('png')
        ->and($data['mime_type'])->toBe('image/png')
        ->and($data['tag'])->toBe('profile')
        ->and($data['directory'])->toBe('users/profiles');

    $this->assertDatabaseHas('temp_files', [
        'tag' => 'profile',
    ]);
});

test('local development signed upload endpoint accepts binary file and verifies signature', function () {
    $user = User::factory()->create();

    $signedUrlResponse = $this->actingAs($user, 'sanctum')
        ->postJson(route('api.v1.signed-url'), [
            'filename' => 'photo.jpg',
            'tag' => 'profile',
        ]);

    $uploadUrl = $signedUrlResponse->json('data.url');
    $key = $signedUrlResponse->json('data.key');
    $disk = $signedUrlResponse->json('data.disk');

    // Perform PUT upload using the generated pre-signed URL
    $fakeFile = UploadedFile::fake()->image('photo.jpg', 200, 200);
    $uploadResponse = $this->put($uploadUrl, [
        'file' => $fakeFile,
    ]);

    $uploadResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'File uploaded successfully to local storage.',
        ]);

    Storage::disk($disk)->assertExists($key);
});

test('user can be created with profile media object and retrieved in resource via media=profile', function () {
    $authUser = User::factory()->create();

    $mediaPayload = [
        'filename' => 'my-avatar-12345.png',
        'directory' => 'users/profiles',
        'size' => 45000,
        'mime_type' => 'image/png',
    ];

    $response = $this->actingAs($authUser, 'sanctum')
        ->postJson(route('api.v1.users.store'), [
            'name' => 'Media User',
            'email' => 'mediauser@example.com',
            'password' => 'SecurePass123!#',
            'profile' => $mediaPayload,
        ]);

    $response->assertStatus(201);
    $userId = $response->json('data.id');

    $this->assertDatabaseHas('media', [
        'directory' => 'users/profiles',
        'mime_type' => 'image/png',
    ]);

    $createdUser = User::with('media')->findOrFail($userId);
    expect($createdUser->getFirstMedia('profile'))->not->toBeNull()
        ->and($createdUser->getFirstMedia('profile')?->mime_type)->toBe('image/png');

    // 1. Retrieve via show endpoint WITH media=profile
    $mediaResponse = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.show', [
            'user' => $userId,
            'media' => 'profile',
        ]));

    $mediaResponse->assertStatus(200);
    $data = $mediaResponse->json('data');

    expect($data)->toHaveKey('profile')
        ->and($data['profile'])->toHaveKey('url')
        ->and($data['profile'])->toHaveKey('mime_type', 'image/png');

    // 2. Retrieve via show endpoint WITHOUT media param (profile should not be loaded)
    $plainResponse = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.show', ['user' => $userId]));

    $plainResponse->assertStatus(200);
    expect($plainResponse->json('data'))->not->toHaveKey('profile');

    // 3. Retrieve via listing with media=profile query param
    $listResponse = $this->actingAs($authUser, 'sanctum')
        ->getJson(route('api.v1.users.index', [
            'media' => 'profile',
            'filter' => ['email' => 'mediauser@example.com'],
        ]));

    $listResponse->assertStatus(200);
    expect($listResponse->json('data.0'))->toHaveKey('profile')
        ->and($listResponse->json('data.0.profile'))->toHaveKey('url');
});

test('validation rule rejects non-image mime types when image rule is used', function () {
    $authUser = User::factory()->create();

    $invalidPayload = [
        'filename' => 'malicious.exe',
        'directory' => 'users/profiles',
        'size' => 1024,
        'mime_type' => 'application/x-msdownload',
    ];

    $response = $this->actingAs($authUser, 'sanctum')
        ->postJson(route('api.v1.users.store'), [
            'name' => 'Bad User',
            'email' => 'baduser@example.com',
            'password' => 'SecurePass123!#',
            'profile' => $invalidPayload,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['profile.mime_type']);
});

test('media prune command deletes orphaned temporary records', function () {
    $oldTemp = TempFile::create([
        'disk' => 'public',
        'directory' => 'uploads',
        'filename' => 'old-unattached.png',
        'tag' => 'default',
    ]);
    $oldTemp->timestamps = false;
    $oldTemp->created_at = now()->subDays(5);
    $oldTemp->save();

    TempFile::create([
        'disk' => 'public',
        'directory' => 'uploads',
        'filename' => 'recent-file.png',
        'tag' => 'default',
    ]);

    Artisan::call('media:prune-temp', ['--days' => 2]);

    $this->assertDatabaseMissing('temp_files', [
        'filename' => 'old-unattached.png',
    ]);

    $this->assertDatabaseHas('temp_files', [
        'filename' => 'recent-file.png',
    ]);
});
