<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\UpdateIdentity;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIdentityRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identityName' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
            'base64EncodedImage' => ['nullable', 'string'],
        ];
    }

    public function identityName(): ?string
    {
        $value = $this->input('identityName');

        return $value !== null ? (string) $value : null;
    }

    public function language(): ?string
    {
        $value = $this->input('language');

        return $value !== null ? (string) $value : null;
    }

    public function base64EncodedImage(): ?string
    {
        $value = $this->input('base64EncodedImage');

        return $value !== null ? (string) $value : null;
    }

    public function requestLanguage(): string
    {
        return (string) ($this->input('requestLanguage') ?? 'en');
    }
}
