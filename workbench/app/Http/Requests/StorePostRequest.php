<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'author_email' => ['nullable', 'email'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'meta' => ['nullable', 'array'],
            'meta.description' => ['nullable', 'string'],
            'meta.keywords' => ['nullable', 'array'],
            'meta.keywords.*' => ['string'],
        ];
    }
}
