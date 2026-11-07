<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchOrdersRequest extends FormRequest
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
            'status' => ['sometimes', 'in:pending,paid,shipped'],
            'sort' => ['sometimes', 'in:created_at,total'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'archived' => ['sometimes', 'boolean'],
        ];
    }
}
