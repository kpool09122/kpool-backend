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
            'consentIdentifier' => ['required', 'uuid'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'consentIdentifier' => $this->route('consentIdentifier'),
        ];
    }

    public function consentIdentifier(): string
    {
        return (string) $this->route('consentIdentifier');
    }
}
