<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SocialLogin\Callback;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SocialLoginCallbackRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ];
    }

    public function provider(): string
    {
        return (string) $this->route('provider');
    }

    public function code(): string
    {
        return (string) $this->input('code');
    }

    public function state(): string
    {
        return (string) $this->input('state');
    }
}
