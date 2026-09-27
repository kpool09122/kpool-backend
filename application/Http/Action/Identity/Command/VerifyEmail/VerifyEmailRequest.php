<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\VerifyEmail;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'authCode' => ['required', 'string'],
        ];
    }

    public function email(): string
    {
        return RequestValue::string($this->input('email'));
    }

    public function authCode(): string
    {
        return RequestValue::string($this->input('authCode'));
    }
}
