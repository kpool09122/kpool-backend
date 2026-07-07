<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\GetIdentityProfile;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class GetIdentityProfileRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identityIdentifier' => ['required', 'uuid'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'identityIdentifier' => $this->route('identityIdentifier'),
        ]);
    }

    public function identityIdentifier(): string
    {
        return (string) $this->route('identityIdentifier');
    }
}
