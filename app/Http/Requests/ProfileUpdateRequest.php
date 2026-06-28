<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'bio'                  => ['nullable', 'string', 'max:500'],
            'orcid'                => ['nullable', 'string', 'regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
            'preferred_task'       => ['nullable', 'in:georef,validate,both'],
            'email_notifications'  => ['boolean'],
            'public_name'          => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email_notifications' => $this->boolean('email_notifications'),
            'public_name'         => $this->boolean('public_name'),
        ]);
    }
}
