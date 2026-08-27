<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\BaseApiController;

class MediaController extends BaseApiController
{
    /**
     * Local storage signed upload handler for development environments.
     */
    public function upload(Request $request): JsonResponse
    {
        $disk = (string) $request->query('disk', config('media.disks.public', 'public'));
        $key = (string) $request->query('key', '');

        if ($key === '') {
            return $this->errorResponse('Invalid storage key provided.', 400);
        }

        // Support both raw binary payload and multipart form data
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $stream = fopen($file->getRealPath(), 'r');
            Storage::disk($disk)->put($key, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        } else {
            $content = $request->getContent();
            Storage::disk($disk)->put($key, $content);
        }

        return $this->successResponse(
            data: [
                'disk' => $disk,
                'key' => $key,
                'size' => Storage::disk($disk)->size($key),
            ],
            message: __('message.media.uploaded')
        );
    }
}
