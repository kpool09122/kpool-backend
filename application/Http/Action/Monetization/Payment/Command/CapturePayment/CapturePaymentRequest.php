<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Payment\Command\CapturePayment;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class CapturePaymentRequest extends FormRequest
{
    use ResolvesLanguage;

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
        return RequestValue::string($this->input('paymentId'));
    }
}
