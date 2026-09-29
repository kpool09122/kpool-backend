<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\VerifySocialLinkingEmail;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class VerifySocialLinkingEmailRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'authCode' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ];
    }

    public function authCode(): string
    {
        return RequestValue::string($this->input('authCode'));
    }
}
