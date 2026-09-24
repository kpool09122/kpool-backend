<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\RegisterWithPasskey;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RegisterWithPasskeyRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'challengeKey' => ['required', 'uuid'],
            'identityName' => ['required', 'string', 'max:32'],
            'displayName' => ['required', 'string', 'max:64'],
            'base64EncodedImage' => ['nullable', 'string'],
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

    public function challengeKey(): string
    {
        return (string) $this->input('challengeKey');
    }

    public function identityName(): string
    {
        return (string) $this->input('identityName');
    }

    public function displayName(): string
    {
        return (string) $this->input('displayName');
    }

    public function base64EncodedImage(): ?string
    {
        $value = $this->input('base64EncodedImage');

        return $value !== null ? (string) $value : null;
    }

    /** @return array<string, mixed> */
    public function credential(): array
    {
        /** @var array<string, mixed> $credential */
        $credential = $this->input('credential');

        return $credential;
    }
}
