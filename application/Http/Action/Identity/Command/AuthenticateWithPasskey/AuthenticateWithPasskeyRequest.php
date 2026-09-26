<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\AuthenticateWithPasskey;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class AuthenticateWithPasskeyRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'challengeKey' => ['required', 'uuid'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
            'credential.response.clientDataJSON' => ['required', 'string'],
            'credential.response.authenticatorData' => ['required', 'string'],
            'credential.response.signature' => ['required', 'string'],
            'credential.response.userHandle' => ['nullable', 'string'],
            'credential.authenticatorAttachment' => ['nullable', 'string'],
            'credential.clientExtensionResults' => ['nullable', 'array'],
        ];
    }

    public function challengeKey(): string
    {
        return (string) $this->input('challengeKey');
    }

    public function credentialId(): string
    {
        return (string) $this->input('credential.id');
    }

    /** @return array<string, mixed> */
    public function credential(): array
    {
        /** @var array<string, mixed> $credential */
        $credential = $this->input('credential');

        return $credential;
    }
}
