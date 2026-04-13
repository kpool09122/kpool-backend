<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\ProvisionMonetizationAccount;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ProvisionMonetizationAccountRequest extends FormRequest
{
    use ResolvesLanguage;

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
}
