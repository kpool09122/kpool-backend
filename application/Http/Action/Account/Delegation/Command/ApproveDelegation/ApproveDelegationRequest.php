<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\ApproveDelegation;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDelegationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'approverIdentifier' => ['required', 'uuid'],
        ];
    }

    public function delegationId(): string
    {
        return (string) $this->route('delegationId');
    }

    public function approverIdentifier(): string
    {
        return (string) $this->input('approverIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
