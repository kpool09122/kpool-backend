<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\Login;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'language' => ['nullable', 'string'],
        ];
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function password(): string
    {
        return (string) $this->input('password');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
