<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Payment\Command\RefundPayment;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'paymentId' => ['required', 'uuid'],
            'refundAmount' => ['required', 'integer', 'min:1'],
            'refundCurrency' => ['required', 'string', 'in:JPY,USD,KRW'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function paymentId(): string
    {
        return (string) $this->input('paymentId');
    }

    public function refundAmount(): int
    {
        return (int) $this->input('refundAmount');
    }

    public function refundCurrency(): string
    {
        return (string) $this->input('refundCurrency');
    }

    public function reason(): string
    {
        return (string) $this->input('reason');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
