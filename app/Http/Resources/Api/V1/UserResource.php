<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'profile' => new MediaResource($this->whenLoadedMedia(config('media.tags.profile'), true)),
            'tokens' => PersonalAccessTokenResource::collection($this->whenLoaded('tokens')),
        ];
    }
}
