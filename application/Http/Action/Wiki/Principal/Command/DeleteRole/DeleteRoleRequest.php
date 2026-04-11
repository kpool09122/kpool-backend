<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DeleteRole;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRoleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function roleId(): string
    {
        return (string) $this->route('roleId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
