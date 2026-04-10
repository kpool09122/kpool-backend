<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\RequestDelegation;

use Illuminate\Foundation\Http\FormRequest;

class RequestDelegationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'affiliationIdentifier' => ['required', 'uuid'],
            'delegateIdentifier' => ['required', 'uuid'],
            'delegatorIdentifier' => ['required', 'uuid'],
        ];
    }

    public function affiliationIdentifier(): string
    {
        return (string) $this->input('affiliationIdentifier');
    }

    public function delegateIdentifier(): string
    {
        return (string) $this->input('delegateIdentifier');
    }

    public function delegatorIdentifier(): string
    {
        return (string) $this->input('delegatorIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
