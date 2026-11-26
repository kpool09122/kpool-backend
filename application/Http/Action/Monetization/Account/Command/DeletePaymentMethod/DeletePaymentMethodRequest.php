<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\DeletePaymentMethod;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class DeletePaymentMethodRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['paymentMethodIdentifier' => ['required', 'uuid'], 'number' => ['prohibited'], 'cardNumber' => ['prohibited'], 'cvc' => ['prohibited']];
    }

    public function paymentMethodIdentifier(): string
    {
        return (string) $this->route('paymentMethodIdentifier');
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return [...parent::validationData(), 'paymentMethodIdentifier' => $this->route('paymentMethodIdentifier')];
    }
}
