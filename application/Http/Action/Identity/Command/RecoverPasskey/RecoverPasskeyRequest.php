<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\RecoverPasskey;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RecoverPasskeyRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'recoveryKey' => ['required', 'uuid'],
            'challengeKey' => ['required', 'uuid'],
            'displayName' => ['required', 'string', 'max:64'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
            'credential.response.clientDataJSON' => ['required', 'string'],
            'credential.response.attestationObject' => ['required', 'string'],
            'credential.response.transports' => ['nullable', 'array'],
            'credential.response.transports.*' => ['string', 'in:ble,hybrid,internal,nfc,smart-card,usb'],
            'credential.authenticatorAttachment' => ['nullable', 'string'],
            'credential.clientExtensionResults' => ['nullable', 'array'],
        ];
    }

    public function recoveryKey(): string
    {
        return (string) $this->input('recoveryKey');
    }

    public function challengeKey(): string
    {
        return (string) $this->input('challengeKey');
    }

    public function displayName(): string
    {
        return (string) $this->input('displayName');
    }

    /** @return array<string, mixed> */
    public function credential(): array
    {
        /** @var array<string, mixed> $credential */
        $credential = $this->input('credential');

        return $credential;
    }
}
