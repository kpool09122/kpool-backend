<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\OnboardSeller;

use Illuminate\Foundation\Http\FormRequest;

class OnboardSellerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monetizationAccountId' => ['required', 'uuid'],
            'email' => ['required', 'email'],
            'countryCode' => ['required', 'string', 'in:JP,KR,US'],
            'refreshUrl' => ['required', 'url'],
            'returnUrl' => ['required', 'url'],
        ];
    }

    public function monetizationAccountId(): string
    {
        return (string) $this->input('monetizationAccountId');
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function countryCode(): string
    {
        return (string) $this->input('countryCode');
    }

    public function refreshUrl(): string
    {
        return (string) $this->input('refreshUrl');
    }

    public function returnUrl(): string
    {
        return (string) $this->input('returnUrl');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
