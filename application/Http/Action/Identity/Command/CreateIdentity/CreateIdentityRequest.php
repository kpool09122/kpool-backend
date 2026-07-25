<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreateIdentity;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class CreateIdentityRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identityName' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'confirmedPassword' => ['required', 'string'],
            'base64EncodedImage' => ['nullable', 'string'],
            'oneTimeToken' => ['nullable', 'string'],
        ];
    }

    public function identityName(): string
    {
        return (string) $this->input('identityName');
    }

    public function email(): string
    {
        return (string) $this->input('email');
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

    public function oneTimeToken(): ?string
    {
        $value = $this->input('oneTimeToken');

        return $value !== null ? (string) $value : null;
    }

    public function requestLanguage(): string
    {
        return (string) ($this->input('requestLanguage') ?? 'en');
    }
}
