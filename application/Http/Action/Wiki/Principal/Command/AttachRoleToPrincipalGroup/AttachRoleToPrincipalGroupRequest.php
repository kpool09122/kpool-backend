<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\AttachRoleToPrincipalGroup;

use Illuminate\Foundation\Http\FormRequest;

class AttachRoleToPrincipalGroupRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roleIdentifier' => ['required', 'uuid'],
        ];
    }

    public function principalGroupId(): string
    {
        return (string) $this->route('principalGroupId');
    }

    public function roleIdentifier(): string
    {
        return (string) $this->input('roleIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
