<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DeletePrincipalGroup;

use Illuminate\Foundation\Http\FormRequest;

class DeletePrincipalGroupRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function principalGroupId(): string
    {
        return (string) $this->route('principalGroupId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
