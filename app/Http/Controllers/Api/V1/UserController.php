<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\User\StoreRequest;
use App\Http\Requests\Api\V1\User\UpdateRequest;

class UserController extends BaseApiController
{
    public function __construct(
        protected readonly UserService $userService
    ) {}

    /**
     * Display a listing of users with dynamic filters, sorting, sparse fieldsets, and pagination.
     */
    public function index(): JsonResponse
    {
        $users = $this->userService->collection();

        return $this->paginatedResponse(
            paginator: $users,
            resourceClass: UserResource::class,
            message: __('message.users.retrieved')
        );
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return $this->createdResponse(
            data: new UserResource($user),
            message: __('message.users.created')
        );
    }

    /**
     * Display the specified user by ID.
     */
    public function show(User $user): JsonResponse
    {
        $user = $this->userService->resource($user);

        return $this->successResponse(
            data: new UserResource($user),
            message: __('message.users.show')
        );
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->update($user, $request->validated());

        return $this->successResponse(
            data: new UserResource($updatedUser),
            message: __('message.users.updated')
        );
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return $this->successResponse(
            message: __('message.users.deleted')
        );
    }
}
