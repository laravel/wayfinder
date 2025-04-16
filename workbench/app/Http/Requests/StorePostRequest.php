<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric'],
            'stock' => ['required', 'integer', Rule::dimensions([2,3])],
            'hidden' => ['boolean'],
            'catalog_id' => ['required', 'integer', 'exists:catalogs,id'],
            'slug' => ['string', 'max:255', 'unique:products'],
            'image' => ['nullable', 'sometimes', File::image()->dimensions(Rule::dimensions()->maxWidth(1024)->maxHeight(1024))->min('1kb')->max('500kb')],
            'code' => ['required', 'string', 'max:255', 'unique:products'],
            'category_id' => ['required']
        ];
    }
}
