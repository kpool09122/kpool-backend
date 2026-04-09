<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\IdentityGroup\Command\DeleteIdentityGroup;

use Illuminate\Foundation\Http\FormRequest;

class DeleteIdentityGroupRequest extends FormRequest
{
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

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
