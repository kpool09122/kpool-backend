<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SocialLogin\Callback;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginCallbackRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
            'language' => ['nullable', 'string'],
        ];
    }

    public function provider(): string
    {
        return (string) $this->route('provider');
    }

    public function code(): string
    {
        return (string) $this->input('code');
    }

    public function state(): string
    {
        return (string) $this->input('state');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
