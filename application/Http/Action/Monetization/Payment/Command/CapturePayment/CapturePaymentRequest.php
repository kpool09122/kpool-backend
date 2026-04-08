<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Payment\Command\CapturePayment;

use Illuminate\Foundation\Http\FormRequest;

class CapturePaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'paymentId' => ['required', 'uuid'],
        ];
    }

    public function paymentId(): string
    {
        return (string) $this->input('paymentId');
    }

    public function language(): string
    {
        return (string) ($this->input('language') ?? 'en');
    }
}
