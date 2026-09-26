<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\StartPasskeyRecoveryWithSocial;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class StartPasskeyRecoveryWithSocialRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identityIdentifier' => ['required', 'uuid'],
        ];
    }

    public function provider(): string
    {
        return (string) $this->route('provider');
    }

    public function identityIdentifier(): string
    {
        return (string) $this->input('identityIdentifier');
    }
}
