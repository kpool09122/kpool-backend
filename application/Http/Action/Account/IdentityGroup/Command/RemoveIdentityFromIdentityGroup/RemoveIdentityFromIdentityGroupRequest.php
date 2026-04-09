<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\IdentityGroup\Command\RemoveIdentityFromIdentityGroup;

use Illuminate\Foundation\Http\FormRequest;

class RemoveIdentityFromIdentityGroupRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identityIdentifier' => ['required', 'uuid'],
        ];
    }

    public function identityGroupId(): string
    {
        return (string) $this->route('identityGroupId');
    }

    public function identityIdentifier(): string
    {
        return (string) $this->input('identityIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
