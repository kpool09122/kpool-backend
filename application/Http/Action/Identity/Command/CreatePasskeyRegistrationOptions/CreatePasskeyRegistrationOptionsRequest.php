<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreatePasskeyRegistrationOptions;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Account\Shared\Domain\ValueObject\AccountType;

class CreatePasskeyRegistrationOptionsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'accountType' => ['nullable', 'string', Rule::enum(AccountType::class)],
            'oneTimeToken' => ['nullable', 'string'],
            'return_to' => ['nullable', 'string'],
        ];
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function accountType(): ?string
    {
        $value = $this->input('accountType');

        return $value !== null ? (string) $value : null;
    }

    public function oneTimeToken(): ?string
    {
        $value = $this->input('oneTimeToken');

        return $value !== null ? (string) $value : null;
    }

    public function returnTo(): ?string
    {
        $value = $this->input('return_to');

        return $value !== null ? (string) $value : null;
    }
}
