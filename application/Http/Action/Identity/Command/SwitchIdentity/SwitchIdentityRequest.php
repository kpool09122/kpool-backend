<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SwitchIdentity;

use Illuminate\Foundation\Http\FormRequest;

class SwitchIdentityRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currentIdentityIdentifier' => ['required', 'uuid'],
            'targetDelegationIdentifier' => ['nullable', 'uuid'],
            'language' => ['nullable', 'string'],
        ];
    }

    public function currentIdentityIdentifier(): string
    {
        return (string) $this->input('currentIdentityIdentifier');
    }

    public function targetDelegationIdentifier(): ?string
    {
        $value = $this->input('targetDelegationIdentifier');

        return $value !== null ? (string) $value : null;
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
