<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreateIdentity;

use Illuminate\Foundation\Http\FormRequest;

class CreateIdentityRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'email' => ['required', 'email'],
            'language' => ['required', 'string'],
            'password' => ['required', 'string'],
            'confirmedPassword' => ['required', 'string'],
            'base64EncodedImage' => ['nullable', 'string'],
            'invitationToken' => ['nullable', 'string'],
        ];
    }

    public function username(): string
    {
        return (string) $this->input('username');
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function language(): string
    {
        return (string) $this->input('language');
    }

    public function password(): string
    {
        return (string) $this->input('password');
    }

    public function confirmedPassword(): string
    {
        return (string) $this->input('confirmedPassword');
    }

    public function base64EncodedImage(): ?string
    {
        $value = $this->input('base64EncodedImage');

        return $value !== null ? (string) $value : null;
    }

    public function invitationToken(): ?string
    {
        $value = $this->input('invitationToken');

        return $value !== null ? (string) $value : null;
    }

    public function requestLanguage(): string
    {
        return (string) ($this->input('requestLanguage') ?? 'en');
    }
}
