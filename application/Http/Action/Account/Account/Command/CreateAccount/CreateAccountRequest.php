<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\CreateAccount;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class CreateAccountRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'accountType' => ['required', 'string'],
            'accountName' => ['required', 'string'],
            'identityIdentifier' => ['nullable', 'uuid'],
        ];
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function accountType(): string
    {
        return (string) $this->input('accountType');
    }

    public function accountName(): string
    {
        return (string) $this->input('accountName');
    }

    public function identityIdentifier(): ?string
    {
        $value = $this->input('identityIdentifier');

        return $value !== null ? (string) $value : null;
    }
}
