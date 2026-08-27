<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use App\Services\SignedUrlService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\SignedUrl\SignedUrlRequest;

class SignedUrlController extends BaseApiController
{
    /**
     * Generate a pre-signed URL for direct binary upload to cloud or local storage.
     */
    public function __invoke(SignedUrlRequest $request, SignedUrlService $signedUrlService): JsonResponse
    {
        /** @var array{filename: string, tag: string} $validated */
        $validated = $request->validated();
        $result = $signedUrlService->create($validated);

        return $this->successResponse(
            data: $result,
            message: __('message.media.signed_url_created')
        );
    }
}
