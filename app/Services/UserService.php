<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Get paginated user collection using the QueryBuilder pipeline.
     * Query parameters are automatically extracted from the HTTP request.
     */
    public function collection(): LengthAwarePaginator|CursorPaginator
    {
        return User::apiQuery()->paginate();
    }

    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        $profileTag = (string) config('media.tags.profile', 'profile');

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (! empty($data[$profileTag])) {
            $user->syncMedia($data[$profileTag], $profileTag);
        }

        return $user;
    }

    /**
     * Find a user resource by model or ID with pipeline processing (fields, includes, appends).
     * Query parameters are automatically extracted from the HTTP request.
     */
    public function resource(User|int|string $user): User
    {
        $id = $user instanceof User ? $user->getKey() : $user;

        return User::apiQuery()->findOrFail($id);
    }

    /**
     * Update an existing user.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $profileTag = (string) config('media.tags.profile', 'profile');

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
        ], fn ($value) => $value !== null);

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        if (array_key_exists($profileTag, $data)) {
            $user->syncMedia($data[$profileTag], $profileTag);
        }

        return $user->fresh();
    }

    /**
     * Delete a user.
     */
    public function delete(User $user): bool
    {
        // Revoke tokens first
        $user->tokens()->delete();

        // Detach media
        $user->detachMedia();

        return (bool) $user->delete();
    }
}
