<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DetachPolicyFromRole;

use Illuminate\Foundation\Http\FormRequest;

class DetachPolicyFromRoleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'policyIdentifier' => ['required', 'uuid'],
        ];
    }

    public function roleId(): string
    {
        return (string) $this->route('roleId');
    }

    public function policyIdentifier(): string
    {
        return (string) $this->input('policyIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
