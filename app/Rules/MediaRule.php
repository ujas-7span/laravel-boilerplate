<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class MediaRule implements ValidationRule
{
    /**
     * @var list<string>
     */
    protected array $allowedMimeTypes = [];

    protected bool $isNullable = true;

    protected bool $isMultiple = false;

    protected ?int $maxSizeKb = null;

    protected ?int $minSizeKb = null;

    /**
     * @param  list<string>  $aggregateTypes
     */
    public function __construct(array $aggregateTypes = ['image'])
    {
        $this->allowedMimeTypes = $this->resolveMimeTypes($aggregateTypes);
    }

    /**
     * Factory for image media validation.
     */
    public static function image(): self
    {
        return new self(['image']);
    }

    /**
     * Factory for document media validation.
     */
    public static function document(): self
    {
        return new self(['document']);
    }

    /**
     * Factory for specific aggregate types.
     *
     * @param  list<string>  $types
     */
    public static function types(array $types): self
    {
        return new self($types);
    }

    /**
     * Mark media field as required.
     */
    public function required(): self
    {
        $this->isNullable = false;

        return $this;
    }

    /**
     * Mark media field as nullable.
     */
    public function nullable(): self
    {
        $this->isNullable = true;

        return $this;
    }

    /**
     * Mark media field as allowing multiple items.
     */
    public function multiple(): self
    {
        $this->isMultiple = true;

        return $this;
    }

    /**
     * Set maximum allowed file size in kilobytes.
     */
    public function maxSize(int $kilobytes): self
    {
        $this->maxSizeKb = $kilobytes;

        return $this;
    }

    /**
     * Set minimum allowed file size in kilobytes.
     */
    public function minSize(int $kilobytes): self
    {
        $this->minSizeKb = $kilobytes;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            if (! $this->isNullable) {
                $fail("The {$attribute} field is required.");
            }

            return;
        }

        if (! is_array($value)) {
            $fail("The {$attribute} must be a valid media object or ID.");

            return;
        }

        $items = $this->isMultiple ? $value : [$value];

        foreach ($items as $index => $item) {
            $itemPath = $this->isMultiple ? "{$attribute}.{$index}" : $attribute;

            if (is_numeric($item)) {
                continue;
            }

            if (! is_array($item)) {
                $fail("The {$itemPath} must be a valid media object.");

                return;
            }

            if (empty($item['filename']) || ! is_string($item['filename'])) {
                $fail("The {$itemPath}.filename is required and must be a string.");
            }

            if (isset($item['mime_type']) && ! empty($this->allowedMimeTypes)) {
                if (! in_array((string) $item['mime_type'], $this->allowedMimeTypes, true)) {
                    $fail("The {$itemPath}.mime_type '{$item['mime_type']}' is not an accepted file format.");
                }
            }

            if (isset($item['size']) && is_numeric($item['size'])) {
                $sizeInKb = (int) $item['size'] / 1024;

                if ($this->maxSizeKb !== null && $sizeInKb > $this->maxSizeKb) {
                    $fail("The {$itemPath}.size may not be greater than {$this->maxSizeKb} KB.");
                }

                if ($this->minSizeKb !== null && $sizeInKb < $this->minSizeKb) {
                    $fail("The {$itemPath}.size must be at least {$this->minSizeKb} KB.");
                }
            }
        }
    }

    /**
     * Traditional Laravel array rules helper for compatibility.
     *
     * @param  list<string>  $tags
     * @return array<string, string>
     */
    public static function rules(string $fieldName, bool $isNullable = true, array $tags = ['image'], bool $multiple = false): array
    {
        $itemPrefix = $multiple ? "{$fieldName}.*" : $fieldName;

        $baseRules = [
            $fieldName => ($isNullable ? 'nullable' : 'required') . '|array',
            "{$itemPrefix}.filename" => "required_with:{$fieldName}|string|max:255",
            "{$itemPrefix}.directory" => 'sometimes|string|max:255',
            "{$itemPrefix}.size" => 'sometimes|integer',
            "{$itemPrefix}.mime_type" => 'sometimes|string',
        ];

        $mimeTypes = (new self($tags))->allowedMimeTypes;
        if (! empty($mimeTypes)) {
            $baseRules["{$itemPrefix}.mime_type"] .= '|in:' . implode(',', $mimeTypes);
        }

        return $baseRules;
    }

    /**
     * Resolve MIME types from aggregate type categories.
     *
     * @param  list<string>  $types
     * @return list<string>
     */
    protected function resolveMimeTypes(array $types): array
    {
        /** @var array<string, list<string>> $configured */
        $configured = (array) config('media.aggregate_types', []);
        $resolved = [];

        foreach ($types as $type) {
            if (isset($configured[$type])) {
                $resolved = array_merge($resolved, $configured[$type]);
            }
        }

        return array_values(array_unique($resolved));
    }
}
