<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreatePrincipal;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class CreatePrincipalRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identityIdentifier' => ['required', 'uuid'],
            'accountIdentifier' => ['required', 'uuid'],
        ];
    }

    public function identityIdentifier(): string
    {
        return RequestValue::string($this->input('identityIdentifier'));
    }

    public function accountIdentifier(): string
    {
        return RequestValue::string($this->input('accountIdentifier'));
    }
}
