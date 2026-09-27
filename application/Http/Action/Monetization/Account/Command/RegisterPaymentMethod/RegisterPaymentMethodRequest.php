<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\RegisterPaymentMethod;

use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPaymentMethodRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monetizationAccountId' => ['required', 'uuid'],
            'paymentMethodId' => ['required', 'string'],
            'type' => ['required', 'string', 'in:card'],
        ];
    }

    public function monetizationAccountId(): string
    {
        return RequestValue::string($this->input('monetizationAccountId'));
    }

    public function paymentMethodId(): string
    {
        return RequestValue::string($this->input('paymentMethodId'));
    }

    public function type(): string
    {
        return RequestValue::string($this->input('type'));
    }
}
