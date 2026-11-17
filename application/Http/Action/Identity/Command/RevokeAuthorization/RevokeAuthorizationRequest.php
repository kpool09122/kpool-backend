<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\RevokeAuthorization;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RevokeAuthorizationRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'connectionIdentifier' => ['required', 'uuid'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'connectionIdentifier' => $this->route('connectionIdentifier'),
        ];
    }

    public function connectionIdentifier(): string
    {
        return (string) $this->route('connectionIdentifier');
    }
}
