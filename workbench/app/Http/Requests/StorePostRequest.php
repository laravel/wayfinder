<?php

namespace App\Http\Requests;

use App\Enums\PostStatus;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'status' => ['nullable', 'in:say "hi",it\'s,back\\slash'],
            'body' => ['required', 'string'],
            'settings' => ['nullable', 'array:theme,timezone'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'author_email' => ['nullable', 'email'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'meta' => ['nullable', 'array'],
            'meta.description' => ['nullable', 'string'],
            'meta.keywords' => ['nullable', 'array'],
            'meta.keywords.*' => ['string'],
            'cover' => ['nullable', 'image'],
            'attachments' => ['array'],
            'attachments.*' => ['file', 'max:2048'],
            'matrix.*.*' => ['integer'],
            'authors' => ['required', 'array'],
            'authors.*.name' => ['required', 'string'],
            'authors.*.email' => ['nullable', 'email'],
            'seo.title' => ['required', 'string'],
            'seo.slug' => ['nullable', 'string'],
            'notes' => ['required', 'max:500'],
            'state' => ['required', Rule::enum(PostStatus::class)],
            'priority' => ['nullable', 'string', Rule::enum(Priority::class)],
            'channel' => ['required', 'string', Rule::in(array_keys(config('wayfinder.unknown_channels', [])))],
        ];
    }
}
