<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Payment\Command\AuthorizePayment;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class AuthorizePaymentRequest extends FormRequest
{
    use ResolvesLanguage;

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
        ];
    }

    public function orderId(): string
    {
        return RequestValue::string($this->input('orderId'));
    }

    public function buyerMonetizationAccountId(): string
    {
        return RequestValue::string($this->input('buyerMonetizationAccountId'));
    }

    public function amount(): int
    {
        return RequestValue::integer($this->input('amount'));
    }

    public function currency(): string
    {
        return RequestValue::string($this->input('currency'));
    }

    public function paymentMethodId(): string
    {
        return RequestValue::string($this->input('paymentMethodId'));
    }

    public function paymentMethodType(): string
    {
        return RequestValue::string($this->input('paymentMethodType'));
    }

    public function paymentMethodLabel(): string
    {
        return RequestValue::string($this->input('paymentMethodLabel'));
    }

    public function paymentMethodRecurringEnabled(): bool
    {
        return (bool) $this->input('paymentMethodRecurringEnabled');
    }
}
