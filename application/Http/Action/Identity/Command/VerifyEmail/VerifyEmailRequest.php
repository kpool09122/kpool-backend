<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\VerifyEmail;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'authCode' => ['required', 'string'],
            'language' => ['nullable', 'string'],
        ];
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function authCode(): string
    {
        return (string) $this->input('authCode');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
