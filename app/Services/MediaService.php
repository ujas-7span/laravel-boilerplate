<?php

namespace App\Services;

use App\Models\Media;
use App\Models\TempFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    /**
     * Resolve media inputs (objects, arrays, IDs) into an array of persisted Media IDs.
     *
     * @return list<int>
     */
    public function resolveMediaIds(mixed $mediaInput, string $tag = 'default'): array
    {
        if ($mediaInput === null || empty($mediaInput)) {
            return [];
        }

        // Single Media instance
        if ($mediaInput instanceof Media) {
            return [(int) $mediaInput->id];
        }

        // Collection of Media instances
        if ($mediaInput instanceof Collection) {
            return $mediaInput->map(fn (mixed $item) => $item instanceof Media ? (int) $item->id : (int) $item)->all();
        }

        // Single integer/numeric ID
        if (is_numeric($mediaInput)) {
            return [(int) $mediaInput];
        }

        // Array of items
        if (is_array($mediaInput)) {
            // Check if it's a single media object payload (has 'filename')
            if (isset($mediaInput['filename'])) {
                $id = $this->createOrUpdateFromPayload($mediaInput, $tag);

                return $id !== null ? [$id] : [];
            }

            // Check if it's a list of media object payloads or numeric IDs
            $ids = [];
            foreach ($mediaInput as $item) {
                if (is_numeric($item)) {
                    $ids[] = (int) $item;
                } elseif ($item instanceof Media) {
                    $ids[] = (int) $item->id;
                } elseif (is_array($item) && isset($item['filename'])) {
                    $id = $this->createOrUpdateFromPayload($item, $tag);
                    if ($id !== null) {
                        $ids[] = $id;
                    }
                }
            }

            return array_values(array_unique($ids));
        }

        return [];
    }

    /**
     * Create or update Media record from validated request payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createOrUpdateFromPayload(array $payload, string $tag = 'default'): ?int
    {
        $filename = (string) $payload['filename'];
        $directory = trim((string) ($payload['directory'] ?? config("media.directories.{$tag}", 'uploads')), '/');

        // Extract extension from filename if present
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if ($extension === '' && isset($payload['extension'])) {
            $extension = (string) $payload['extension'];
        }

        // Strip extension from filename column to store cleanly
        $cleanFilename = pathinfo($filename, PATHINFO_FILENAME);
        $mimeType = (string) ($payload['mime_type'] ?? config("media.mime_types.{$extension}", 'application/octet-stream'));
        $aggregateType = $this->resolveAggregateType($mimeType);
        $size = (int) ($payload['size'] ?? 0);

        $isPrivate = in_array($tag, (array) config('media.private_tags', []), true);
        $disk = (string) ($payload['disk'] ?? ($isPrivate ? config('media.disks.private', 'local') : config('media.disks.public', 'public')));

        /** @var Media $media */
        $media = Media::updateOrCreate(
            [
                'directory' => $directory,
                'filename' => $cleanFilename,
                'extension' => $extension,
            ],
            [
                'disk' => $disk,
                'mime_type' => $mimeType,
                'aggregate_type' => $aggregateType,
                'size' => $size,
                'custom_properties' => $payload['custom_properties'] ?? null,
            ]
        );

        // Delete from temp_files table if tracked
        TempFile::where('directory', $directory)
            ->where(function ($query) use ($cleanFilename, $extension) {
                $query->where('filename', $cleanFilename)
                    ->orWhere('filename', "{$cleanFilename}.{$extension}");
            })
            ->delete();

        return (int) $media->id;
    }

    /**
     * Resolve aggregate type (image, document, video, etc.) from MIME type.
     */
    public function resolveAggregateType(string $mimeType): string
    {
        /** @var array<string, list<string>> $aggregateTypes */
        $aggregateTypes = (array) config('media.aggregate_types', []);

        foreach ($aggregateTypes as $type => $mimes) {
            if (in_array($mimeType, $mimes, true)) {
                return $type;
            }
        }

        return 'all';
    }

    /**
     * Delete a media item from storage and database.
     */
    public function destroyMedia(Media $media): bool
    {
        Storage::disk($media->disk)->delete($media->getPath());

        return (bool) $media->delete();
    }

    /**
     * Prune orphaned temporary files that were never attached to any entity.
     */
    public function pruneExpiredTempFiles(?int $days = null): int
    {
        $days = $days ?? (int) config('media.temp_file_delete_after_days', 2);
        $cutoff = now()->subDays($days);

        $tempFiles = TempFile::where('created_at', '<', $cutoff)->get();
        $prunedCount = 0;

        foreach ($tempFiles as $temp) {
            $path = $temp->directory !== '' ? "{$temp->directory}/{$temp->filename}" : $temp->filename;
            Storage::disk($temp->disk)->delete($path);
            $temp->delete();
            $prunedCount++;
        }

        return $prunedCount;
    }
}
