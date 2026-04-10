<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\RevokeDelegation;

use Illuminate\Foundation\Http\FormRequest;

class RevokeDelegationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'revokerIdentifier' => ['required', 'uuid'],
        ];
    }

    public function delegationId(): string
    {
        return (string) $this->route('delegationId');
    }

    public function revokerIdentifier(): string
    {
        return (string) $this->input('revokerIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
