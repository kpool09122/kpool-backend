<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\IdentityGroup\Command\DeleteIdentityGroup;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class DeleteIdentityGroupRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function identityGroupId(): string
    {
        return (string) $this->route('identityGroupId');
    }
}
