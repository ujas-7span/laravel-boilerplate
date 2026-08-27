<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AuthResource extends JsonResource
{
    /**
     * @param  User  $resource
     */
    public function __construct(
        mixed $resource,
        protected readonly string $token,
        protected readonly string $tokenType = 'Bearer'
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource),
            'token' => $this->token,
            'token_type' => $this->tokenType,
        ];
    }
}
