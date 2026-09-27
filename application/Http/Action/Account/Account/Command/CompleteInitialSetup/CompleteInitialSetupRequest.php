<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\CompleteInitialSetup;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Account\Shared\Domain\ValueObject\AccountType;

class CompleteInitialSetupRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'accountType' => ['required', 'string', Rule::enum(AccountType::class)],
        ];
    }

    public function accountType(): string
    {
        return RequestValue::string($this->input('accountType'));
    }
}
