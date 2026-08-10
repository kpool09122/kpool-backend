<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\AccountTypeChangeRequest\Command\RequestAccountTypeChange;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RequestAccountTypeChangeRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['accountIdentifier' => ['required', 'uuid'], 'requestedAccountType' => ['required', 'string']];
    }

    public function accountIdentifier(): string
    {
        return (string) $this->input('accountIdentifier');
    }

    public function requestedAccountType(): string
    {
        return (string) $this->input('requestedAccountType');
    }
}
