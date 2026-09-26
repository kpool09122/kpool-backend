<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\UpdatePasskey;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasskeyRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'passkeyIdentifier' => ['required', 'uuid'],
            'displayName' => ['required', 'string', 'min:1', 'max:64'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'passkeyIdentifier' => $this->route('passkeyIdentifier'),
        ];
    }

    public function passkeyIdentifier(): string
    {
        return (string) $this->route('passkeyIdentifier');
    }

    public function displayName(): string
    {
        return (string) $this->input('displayName');
    }
}
