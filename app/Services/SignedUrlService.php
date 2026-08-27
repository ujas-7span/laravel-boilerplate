<?php

namespace App\Services;

use Aws\S3\S3Client;
use App\Models\TempFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

class SignedUrlService
{
    /**
     * Generate a pre-signed upload URL for direct cloud/local storage.
     *
     * @param  array{filename: string, tag: string}  $inputs
     * @return array<string, mixed>
     */
    public function create(array $inputs): array
    {
        $tag = $inputs['tag'];
        $originalFilename = $inputs['filename'];

        // Extract extension and infer MIME type automatically from filename
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $mimeType = (string) (config("media.mime_types.{$extension}") ?? 'application/octet-stream');

        // Resolve target disk and directory based on tag
        $isPrivate = in_array($tag, (array) config('media.private_tags', []), true);
        $diskName = $isPrivate
            ? config('media.disks.private', 'local')
            : config('media.disks.public', 'public');

        $directory = trim((string) config("media.directories.{$tag}", 'uploads'), '/');

        // Generate sanitized, collision-resistant filename
        $baseName = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME));
        $baseName = $baseName !== '' ? $baseName : 'file';
        $uniqueFilename = $baseName . '-' . Str::lower(Str::random(10));
        $fullFileName = $extension !== '' ? "{$uniqueFilename}.{$extension}" : $uniqueFilename;
        $key = $directory !== '' ? "{$directory}/{$fullFileName}" : $fullFileName;

        $expirationMinutes = (int) config('media.signed_url_expiration_minutes', 20);
        $expiresAt = now()->addMinutes($expirationMinutes);

        $diskConfig = (array) config("filesystems.disks.{$diskName}", []);
        $driver = $diskConfig['driver'] ?? 'local';

        // If driver is S3-compatible, generate AWS S3 pre-signed PutObject URL
        if ($driver === 's3' && ! empty($diskConfig['key']) && ! empty($diskConfig['secret'])) {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $diskConfig['region'] ?? 'us-east-1',
                'endpoint' => $diskConfig['endpoint'] ?? null,
                'use_path_style_endpoint' => $diskConfig['use_path_style_endpoint'] ?? false,
                'credentials' => [
                    'key' => $diskConfig['key'],
                    'secret' => $diskConfig['secret'],
                ],
            ]);

            $root = trim((string) ($diskConfig['root'] ?? ''), '/');
            $s3Key = $root !== '' ? "{$root}/{$key}" : $key;

            $command = $s3Client->getCommand('PutObject', [
                'Bucket' => $diskConfig['bucket'] ?? '',
                'Key' => $s3Key,
                'ContentType' => $mimeType,
            ]);

            $presignedRequest = $s3Client->createPresignedRequest($command, "+{$expirationMinutes} minutes");
            $uploadUrl = (string) $presignedRequest->getUri();
        } else {
            // Local development signed upload route (works offline with zero AWS credentials)
            $uploadUrl = URL::temporarySignedRoute(
                'api.v1.media.upload',
                $expiresAt,
                [
                    'disk' => $diskName,
                    'key' => $key,
                ]
            );
        }

        // Record temporary file in database for pruning tracking
        TempFile::create([
            'disk' => $diskName,
            'directory' => $directory,
            'filename' => $fullFileName,
            'tag' => $tag,
        ]);

        // Resolve readable file URL
        $fileUrl = $isPrivate
            ? Storage::disk($diskName)->temporaryUrl($key, $expiresAt)
            : Storage::disk($diskName)->url($key);

        return [
            'url' => $uploadUrl,
            'file_url' => $fileUrl,
            'key' => $key,
            'directory' => $directory,
            'filename' => $uniqueFilename,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'tag' => $tag,
            'disk' => $diskName,
        ];
    }
}
