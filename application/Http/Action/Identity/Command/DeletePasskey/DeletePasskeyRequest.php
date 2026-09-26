<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\DeletePasskey;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class DeletePasskeyRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'passkeyIdentifier' => ['required', 'uuid'],
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
}
