<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SwitchIdentity;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SwitchIdentityRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'targetDelegationIdentifier' => ['nullable', 'uuid'],
        ];
    }

    public function targetDelegationIdentifier(): ?string
    {
        $value = $this->input('targetDelegationIdentifier');

        return $value !== null ? (string) $value : null;
    }
}
