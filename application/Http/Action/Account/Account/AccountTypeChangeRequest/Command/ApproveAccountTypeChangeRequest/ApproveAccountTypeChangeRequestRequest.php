<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\AccountTypeChangeRequest\Command\ApproveAccountTypeChangeRequest;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ApproveAccountTypeChangeRequestRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function requestId(): string
    {
        return (string) $this->route('requestId');
    }
}
