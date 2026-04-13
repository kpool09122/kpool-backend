<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DetachRoleFromPrincipalGroup;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class DetachRoleFromPrincipalGroupRequest extends FormRequest
{
    use ResolvesLanguage;

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
}
