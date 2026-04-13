<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\IdentityGroup\Command\CreateIdentityGroup;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class CreateIdentityGroupRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountIdentifier' => ['required', 'uuid'],
            'name' => ['required', 'string'],
            'role' => ['required', 'string'],
        ];
    }

    public function accountIdentifier(): string
    {
        return (string) $this->input('accountIdentifier');
    }

    public function name(): string
    {
        return (string) $this->input('name');
    }

    public function role(): string
    {
        return (string) $this->input('role');
    }
}
