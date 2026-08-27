<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\Eloquent\MissingAttributeException;

test('strict mode prevents lazy loading un-eager-loaded relationships', function () {
    $user = User::factory()->create();

    // In non-production, attempting to lazy-load an un-eager-loaded relationship throws exception
    expect(fn () => $user->tokens()->get())
        ->not->toThrow(LazyLoadingViolationException::class); // direct query builder is allowed

    // Accessing dynamic relation property triggers lazy-load prevention
    $freshUser = User::find($user->id);
    expect($freshUser->relationLoaded('tokens'))->toBeFalse();
});

test('strict mode prevents accessing unselected missing attributes under partial selects', function () {
    $user = User::factory()->create([
        'name' => 'Alice Doe',
        'email' => 'alice@example.com',
    ]);

    // Select only 'id' and 'name'
    $partialUser = User::select(['id', 'name'])->find($user->id);

    expect($partialUser->name)->toBe('Alice Doe')
        ->and(fn () => $partialUser->email)->toThrow(MissingAttributeException::class);
});

test('strict mode is disabled in production to keep production running safely', function () {
    // Simulate production environment
    Model::shouldBeStrict(false);

    $user = User::factory()->create();
    $partialUser = User::select(['id', 'name'])->find($user->id);

    // In production, accessing unselected attribute returns null instead of throwing an exception
    expect($partialUser->email)->toBeNull();

    // Restore strict mode for other tests
    Model::shouldBeStrict(true);
});
