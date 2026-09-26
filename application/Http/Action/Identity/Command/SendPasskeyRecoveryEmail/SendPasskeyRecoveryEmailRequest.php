<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SendPasskeyRecoveryEmail;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SendPasskeyRecoveryEmailRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }
}
