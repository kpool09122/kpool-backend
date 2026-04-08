<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Billing\Command\RecordPayment;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoiceId' => ['required', 'uuid'],
            'paymentIdentifier' => ['required', 'uuid'],
        ];
    }

    public function invoiceId(): string
    {
        return (string) $this->input('invoiceId');
    }

    public function paymentIdentifier(): string
    {
        return (string) $this->input('paymentIdentifier');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
