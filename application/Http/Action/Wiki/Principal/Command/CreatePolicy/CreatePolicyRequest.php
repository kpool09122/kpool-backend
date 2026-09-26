<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreatePolicy;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class CreatePolicyRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'statements' => ['required', 'array'],
            'accountId' => ['nullable', 'uuid'],
        ];
    }

    public function name(): string
    {
        return (string) $this->input('name');
    }

    /**
     * @return array<int, mixed>
     */
    public function statements(): array
    {
        return (array) $this->input('statements');
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        $accountId = $this->input('accountId');

        return is_string($accountId) ? new AccountIdentifier($accountId) : null;
    }
}
