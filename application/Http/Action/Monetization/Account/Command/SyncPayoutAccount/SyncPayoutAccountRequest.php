<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\SyncPayoutAccount;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SyncPayoutAccountRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'connectedAccountId' => ['required', 'string'],
            'externalAccountId' => ['required', 'string'],
            'eventType' => ['required', 'string'],
            'bankName' => ['nullable', 'string'],
            'last4' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'accountHolderType' => ['nullable', 'string'],
            'isDefault' => ['boolean'],
        ];
    }

    public function connectedAccountId(): string
    {
        return (string) $this->input('connectedAccountId');
    }

    public function externalAccountId(): string
    {
        return (string) $this->input('externalAccountId');
    }

    public function eventType(): string
    {
        return (string) $this->input('eventType');
    }

    public function bankName(): ?string
    {
        $value = $this->input('bankName');

        return $value !== null ? (string) $value : null;
    }

    public function last4(): ?string
    {
        $value = $this->input('last4');

        return $value !== null ? (string) $value : null;
    }

    public function country(): ?string
    {
        $value = $this->input('country');

        return $value !== null ? (string) $value : null;
    }

    public function currency(): ?string
    {
        $value = $this->input('currency');

        return $value !== null ? (string) $value : null;
    }

    public function accountHolderType(): ?string
    {
        $value = $this->input('accountHolderType');

        return $value !== null ? (string) $value : null;
    }

    public function isDefault(): bool
    {
        return (bool) $this->input('isDefault', false);
    }
}
