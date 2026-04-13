<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\DelegationPermission\Command\RevokeDelegationPermission;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RevokeDelegationPermissionRequest extends FormRequest
{
    use ResolvesLanguage;

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
}
