<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\AttachPolicyToRole;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class AttachPolicyToRoleRequest extends FormRequest
{
    use ResolvesLanguage;

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
}
