<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\RegisterPaymentMethod;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPaymentMethodRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['setupIntentIdentifier' => ['required', 'string', 'max:255', 'regex:/^seti_[A-Za-z0-9]+$/D'], 'number' => ['prohibited'], 'cardNumber' => ['prohibited'], 'cvc' => ['prohibited']];
    }

    public function setupIntentIdentifier(): string
    {
        return RequestValue::string($this->input('setupIntentIdentifier'));
    }
}
