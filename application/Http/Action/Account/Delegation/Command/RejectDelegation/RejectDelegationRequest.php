<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\RejectDelegation;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RejectDelegationRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function delegationId(): string
    {
        return (string) $this->route('delegationId');
    }
}
