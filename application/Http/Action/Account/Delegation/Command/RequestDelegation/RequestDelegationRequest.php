<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\RequestDelegation;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RequestDelegationRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['targetAccountIdentifier' => ['required', 'uuid']];
    }

    public function targetAccountIdentifier(): string
    {
        return (string) $this->input('targetAccountIdentifier');
    }
}
