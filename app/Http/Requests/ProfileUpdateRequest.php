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
            // Deliberately no 'orcid' rule: it must only ever be set via the actual ORCID
            // OAuth connection (SocialiteController), never typed in free-text — otherwise
            // anyone could claim someone else's ORCID iD with zero proof of ownership,
            // defeating the point of showing it as a "verified researcher" badge.
            'preferred_task'       => ['nullable', 'in:georef,validate,both'],
            'email_notifications'  => ['boolean'],
            'public_name'          => ['boolean'],
            'avatar'               => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
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
