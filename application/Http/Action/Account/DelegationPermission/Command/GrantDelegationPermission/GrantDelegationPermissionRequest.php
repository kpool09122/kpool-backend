<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\DelegationPermission\Command\GrantDelegationPermission;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class GrantDelegationPermissionRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'principalGroupIdentifier' => ['required', 'uuid'],
            'targetAccountIdentifier' => ['required', 'uuid'],
            'affiliationIdentifier' => ['required', 'uuid'],
        ];
    }

    public function principalGroupIdentifier(): string
    {
        return (string) $this->input('principalGroupIdentifier');
    }

    public function targetAccountIdentifier(): string
    {
        return (string) $this->input('targetAccountIdentifier');
    }

    public function affiliationIdentifier(): string
    {
        return (string) $this->input('affiliationIdentifier');
    }
}
