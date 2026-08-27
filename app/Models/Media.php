<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $disk
 * @property string $directory
 * @property string $filename
 * @property string|null $extension
 * @property string|null $mime_type
 * @property string $aggregate_type
 * @property int $size
 * @property array<string, mixed>|null $custom_properties
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Pivot|object{tag: string, order: int} $pivot
 */
class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'disk',
        'directory',
        'filename',
        'extension',
        'mime_type',
        'aggregate_type',
        'size',
        'custom_properties',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
        'custom_properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relative path inside storage disk.
     */
    public function getPath(): string
    {
        $file = $this->extension ? "{$this->filename}.{$this->extension}" : $this->filename;

        return $this->directory !== '' ? "{$this->directory}/{$file}" : $file;
    }

    /**
     * Check if media is stored on a private disk.
     */
    public function isPrivate(): bool
    {
        return $this->disk === config('media.disks.private', 'local')
            || $this->disk === 's3-private'
            || in_array($this->disk, (array) config('media.private_tags', []), true);
    }

    /**
     * Check if media is an image.
     */
    public function isImage(): bool
    {
        return $this->aggregate_type === 'image';
    }

    /**
     * Resolve public or pre-signed URL for this media item.
     */
    public function getUrl(?DateTimeInterface $expiration = null): string
    {
        $path = $this->getPath();
        $disk = Storage::disk($this->disk);

        if ($this->isPrivate()) {
            $expiration = $expiration ?? now()->addMinutes((int) config('media.signed_url_expiration_minutes', 20));

            return $disk->temporaryUrl($path, $expiration);
        }

        if (config('media.cdn_enable') && ! empty(config('media.cdn_url'))) {
            return rtrim((string) config('media.cdn_url'), '/') . '/' . ltrim($path, '/');
        }

        return $disk->url($path);
    }

    /**
     * Get temporary signed URL directly with specified expiration.
     */
    public function getTemporaryUrl(?DateTimeInterface $expiration = null): string
    {
        $expiration = $expiration ?? now()->addMinutes((int) config('media.signed_url_expiration_minutes', 20));

        return Storage::disk($this->disk)->temporaryUrl($this->getPath(), $expiration);
    }

    /**
     * Computed accessor for URL.
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->getUrl()
        );
    }
}
