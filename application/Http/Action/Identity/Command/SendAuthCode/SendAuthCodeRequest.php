<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SendAuthCode;

use Illuminate\Foundation\Http\FormRequest;

class SendAuthCodeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'language' => ['nullable', 'string'],
        ];
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
