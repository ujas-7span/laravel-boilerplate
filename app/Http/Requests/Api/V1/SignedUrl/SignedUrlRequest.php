<?php

namespace App\Http\Requests\Api\V1\SignedUrl;

use Illuminate\Validation\Rule;
use App\Http\Requests\Api\BaseApiRequest;

class SignedUrlRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allowedTags = array_keys((array) config('media.tags', ['default' => 'default']));

        return [
            'filename' => ['required', 'string', 'max:255'],
            'tag' => ['required', 'string', Rule::in($allowedTags)],
        ];
    }

    /**
     * Custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'filename' => 'file name',
            'tag' => 'media tag',
        ];
    }
}
