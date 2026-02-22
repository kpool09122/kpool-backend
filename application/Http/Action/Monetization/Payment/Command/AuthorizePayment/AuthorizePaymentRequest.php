<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Payment\Command\AuthorizePayment;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizePaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'orderId' => ['required', 'uuid'],
            'buyerMonetizationAccountId' => ['required', 'uuid'],
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'in:JPY,USD,KRW'],
            'paymentMethodId' => ['required', 'uuid'],
            'paymentMethodType' => ['required', 'string', 'in:card,bank_transfer,wallet'],
            'paymentMethodLabel' => ['required', 'string'],
            'paymentMethodRecurringEnabled' => ['required', 'boolean'],
            'stripePaymentMethodId' => ['required', 'string', 'regex:/^pm_/'],
        ];
    }

    public function orderId(): string
    {
        return (string) $this->input('orderId');
    }

    public function buyerMonetizationAccountId(): string
    {
        return (string) $this->input('buyerMonetizationAccountId');
    }

    public function amount(): int
    {
        return (int) $this->input('amount');
    }

    public function currency(): string
    {
        return (string) $this->input('currency');
    }

    public function paymentMethodId(): string
    {
        return (string) $this->input('paymentMethodId');
    }

    public function paymentMethodType(): string
    {
        return (string) $this->input('paymentMethodType');
    }

    public function paymentMethodLabel(): string
    {
        return (string) $this->input('paymentMethodLabel');
    }

    public function paymentMethodRecurringEnabled(): bool
    {
        return (bool) $this->input('paymentMethodRecurringEnabled');
    }

    public function stripePaymentMethodId(): string
    {
        return (string) $this->input('stripePaymentMethodId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
