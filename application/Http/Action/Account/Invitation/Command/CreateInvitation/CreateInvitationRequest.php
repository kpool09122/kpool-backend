<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Invitation\Command\CreateInvitation;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvitationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountIdentifier' => ['required', 'uuid'],
            'inviterIdentityIdentifier' => ['required', 'uuid'],
            'emails' => ['required', 'array', 'min:1'],
            'emails.*' => ['required', 'email', 'distinct'],
        ];
    }

    public function accountIdentifier(): string
    {
        return (string) $this->input('accountIdentifier');
    }

    public function inviterIdentityIdentifier(): string
    {
        return (string) $this->input('inviterIdentityIdentifier');
    }

    /**
     * @return array<string>
     */
    public function emails(): array
    {
        return (array) $this->input('emails');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
