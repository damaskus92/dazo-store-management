<?php

namespace App\Http\Requests;

use App\Enums\StoreLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'level' => ['sometimes', 'required', Rule::enum(StoreLevel::class)],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'parent_id' => ['nullable', 'exists:stores,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Store name is required when provided.',
            'level.required' => 'Store level is required when provided.',
            'level.in' => 'Store level must be one of: center, branch, retail.',
            'parent_id.exists' => 'Parent store not found.',
        ];
    }
}
