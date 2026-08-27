<?php

namespace App\Traits;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Trait to equip any Eloquent Model with rich Media attachments.
 *
 * Supports tagging (e.g. 'profile', 'banner', 'document'), ordering,
 * request-driven eager-loading with ?media=tag, and resource helper methods.
 */
trait HasMedia
{
    /**
     * Polymorphic Many-to-Many relationship with Media.
     *
     * @return MorphToMany<Media, $this>
     */
    public function media(): MorphToMany
    {
        return $this->morphToMany(
            Media::class,
            'mediable',
            'mediables'
        )
            ->withPivot(['tag', 'order'])
            ->withTimestamps()
            ->orderBy('mediables.order');
    }

    /**
     * Get all media attached under a specific tag.
     * Reads from memory if already eager-loaded to prevent extra queries.
     *
     * @return EloquentCollection<int, Media>
     */
    public function getMedia(string $tag = 'default'): EloquentCollection
    {
        if ($this->relationLoaded('media')) {
            /** @var EloquentCollection<int, Media> $loaded */
            $loaded = $this->media->filter(fn (Media $m) => ($m->pivot->tag ?? 'default') === $tag)->values();

            return $loaded;
        }

        /** @var EloquentCollection<int, Media> $results */
        $results = $this->media()->wherePivot('tag', $tag)->get();

        return $results;
    }

    /**
     * Get the first media attached under a specific tag.
     */
    public function getFirstMedia(string $tag = 'default'): ?Media
    {
        return $this->getMedia($tag)->first();
    }

    /**
     * Get URL of the first media item for a tag, or return fallback URL.
     */
    public function getFirstMediaUrl(string $tag = 'default', ?string $fallback = null): ?string
    {
        return $this->getFirstMedia($tag)?->getUrl() ?? $fallback;
    }

    /**
     * Attach media to this model under a specific tag.
     *
     * @param  Media|int|string|array<string, mixed>|iterable<mixed>  $mediaInput
     */
    public function attachMedia(mixed $mediaInput, string $tag = 'default'): static
    {
        $service = app(MediaService::class);
        $mediaIds = $service->resolveMediaIds($mediaInput, $tag);

        if (! empty($mediaIds)) {
            $maxOrder = (int) $this->media()->wherePivot('tag', $tag)->max('order');
            $pivotData = [];
            foreach ($mediaIds as $index => $id) {
                $pivotData[$id] = [
                    'tag' => $tag,
                    'order' => $maxOrder + $index + 1,
                ];
            }

            $this->media()->attach($pivotData);
        }

        return $this;
    }

    /**
     * Sync media attached to this model for a specific tag.
     * Existing media under this tag is replaced by the new input.
     *
     * @param  Media|int|string|array<string, mixed>|iterable<mixed>|null  $mediaInput
     */
    public function syncMedia(mixed $mediaInput, string $tag = 'default'): static
    {
        // Detach existing media with this tag
        $this->media()->wherePivot('tag', $tag)->detach();

        if ($mediaInput !== null && ! empty($mediaInput)) {
            $this->attachMedia($mediaInput, $tag);
        }

        return $this;
    }

    /**
     * Detach all media attached to this model under a specific tag.
     */
    public function detachMedia(string $tag = 'default'): static
    {
        $this->media()->wherePivot('tag', $tag)->detach();

        return $this;
    }

    /**
     * Helper for API Resources when media is requested via ?media=tag or ?include=media.
     * Returns matching Media or MissingValue if not requested.
     *
     * @return EloquentCollection<int, Media>|Media|MissingValue|null
     */
    public function whenLoadedMedia(string $tag = 'default', bool $isSingleResource = false): EloquentCollection|Media|MissingValue|null
    {
        if (! $this->relationLoaded('media')) {
            return new MissingValue;
        }

        $mediaParam = request()->input('media') ?? request()->input('include');
        if (! empty($mediaParam)) {
            $requestedTags = is_array($mediaParam) ? $mediaParam : explode(',', (string) $mediaParam);
            $requestedTags = array_map('trim', $requestedTags);

            if (in_array('media', $requestedTags, true) || in_array('all', $requestedTags, true) || in_array($tag, $requestedTags, true)) {
                if ($isSingleResource) {
                    return $this->getMedia($tag)->first();
                }

                return $this->getMedia($tag);
            }
        }

        return new MissingValue;
    }
}
