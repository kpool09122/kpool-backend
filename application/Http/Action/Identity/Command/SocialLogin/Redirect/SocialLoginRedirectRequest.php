<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SocialLogin\Redirect;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRedirectRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountType' => ['nullable', 'string'],
            'invitationToken' => ['nullable', 'string'],
        ];
    }

    public function provider(): string
    {
        return (string) $this->route('provider');
    }

    public function accountType(): ?string
    {
        $value = $this->input('accountType');

        return $value !== null ? (string) $value : null;
    }

    public function invitationToken(): ?string
    {
        $value = $this->input('invitationToken');

        return $value !== null ? (string) $value : null;
    }
}
