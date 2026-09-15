<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreateRole;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreateRoleRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'policies' => ['nullable', 'array'],
            'policies.*' => ['uuid'],
            'accountId' => ['nullable', 'uuid'],
        ];
    }

    public function name(): string
    {
        return (string) $this->input('name');
    }

    /**
     * @return string[]|null
     */
    public function policies(): ?array
    {
        return $this->input('policies');
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        $accountId = $this->input('accountId');

        return is_string($accountId) ? new AccountIdentifier($accountId) : null;
    }
}
