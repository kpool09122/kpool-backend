<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Invitation\Command\InviteMember;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountIdentifier' => ['required', 'uuid'],
            'inviterPrincipalIdentifier' => ['required', 'uuid'],
            'emails' => ['required', 'array', 'min:1'],
            'emails.*' => ['required', 'email', 'distinct'],
        ];
    }

    public function accountIdentifier(): string
    {
        return RequestValue::string($this->input('accountIdentifier'));
    }

    public function inviterPrincipalIdentifier(): string
    {
        return RequestValue::string($this->input('inviterPrincipalIdentifier'));
    }

    /**
     * @return array<string>
     */
    public function emails(): array
    {
        return RequestValue::strings($this->input('emails'));
    }
}
