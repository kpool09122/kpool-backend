<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\DelegationPermission\Command\GrantDelegationPermission;

use Illuminate\Foundation\Http\FormRequest;

class GrantDelegationPermissionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identityGroupIdentifier' => ['required', 'uuid'],
            'targetAccountIdentifier' => ['required', 'uuid'],
            'affiliationIdentifier' => ['required', 'uuid'],
        ];
    }

    public function identityGroupIdentifier(): string
    {
        return (string) $this->input('identityGroupIdentifier');
    }

    public function targetAccountIdentifier(): string
    {
        return (string) $this->input('targetAccountIdentifier');
    }

    public function affiliationIdentifier(): string
    {
        return (string) $this->input('affiliationIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
