<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\DelegationPermission\Command\RevokeDelegationPermission;

use Illuminate\Foundation\Http\FormRequest;

class RevokeDelegationPermissionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function delegationPermissionId(): string
    {
        return (string) $this->route('delegationPermissionId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
