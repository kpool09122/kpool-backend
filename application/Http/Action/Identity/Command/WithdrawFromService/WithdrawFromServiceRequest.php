<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\WithdrawFromService;

use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;
use Source\Identity\Domain\ValueObject\IdentityName;

class WithdrawFromServiceRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirmationIdentityName' => ['required', 'string', 'max:' . IdentityName::MAX_LENGTH],
        ];
    }

    public function confirmationIdentityName(): string
    {
        return RequestValue::string($this->input('confirmationIdentityName'));
    }
}
