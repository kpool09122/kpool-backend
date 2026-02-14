<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\ProvisionMonetizationAccount;

use Illuminate\Foundation\Http\FormRequest;

class ProvisionMonetizationAccountRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountId' => ['required', 'uuid'],
        ];
    }

    public function accountId(): string
    {
        return (string) $this->input('accountId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
