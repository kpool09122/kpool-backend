<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\Logout;

use Illuminate\Foundation\Http\FormRequest;

class LogoutRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'language' => ['nullable', 'string'],
        ];
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
