<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Billing\Command\CreateInvoice;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Action\Support\RequestValue;
use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'orderIdentifier' => ['required', 'uuid'],
            'buyerMonetizationAccountIdentifier' => ['required', 'uuid'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string'],
            'lines.*.unitPriceAmount' => ['required', 'integer', 'min:0'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'shippingCostAmount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string'],
            'discountPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'discountCode' => ['nullable', 'string'],
            'taxLines' => ['nullable', 'array'],
            'taxLines.*.label' => ['required_with:taxLines', 'string'],
            'taxLines.*.rate' => ['required_with:taxLines', 'integer', 'min:0', 'max:100'],
            'taxLines.*.inclusive' => ['required_with:taxLines', 'boolean'],
            'sellerCountry' => ['required', 'string'],
            'sellerRegistered' => ['required', 'boolean'],
            'qualifiedInvoiceRequired' => ['required', 'boolean'],
            'buyerCountry' => ['required', 'string'],
            'buyerIsBusiness' => ['required', 'boolean'],
            'paidByCard' => ['required', 'boolean'],
            'registrationNumber' => ['nullable', 'string'],
        ];
    }

    public function orderIdentifier(): string
    {
        return RequestValue::string($this->input('orderIdentifier'));
    }

    public function buyerMonetizationAccountIdentifier(): string
    {
        return RequestValue::string($this->input('buyerMonetizationAccountIdentifier'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lines(): array
    {
        return RequestValue::objects($this->input('lines', []));
    }

    public function shippingCostAmount(): int
    {
        return RequestValue::integer($this->input('shippingCostAmount'));
    }

    public function currency(): string
    {
        return RequestValue::string($this->input('currency'));
    }

    public function discountPercentage(): ?int
    {
        $value = $this->input('discountPercentage');

        return $value !== null ? RequestValue::integer($value) : null;
    }

    public function discountCode(): ?string
    {
        $value = $this->input('discountCode');

        return $value !== null ? RequestValue::string($value) : null;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function taxLines(): ?array
    {
        $value = $this->input('taxLines');

        return $value !== null ? RequestValue::objects($value) : null;
    }

    public function sellerCountry(): string
    {
        return RequestValue::string($this->input('sellerCountry'));
    }

    public function sellerRegistered(): bool
    {
        return (bool) $this->input('sellerRegistered');
    }

    public function qualifiedInvoiceRequired(): bool
    {
        return (bool) $this->input('qualifiedInvoiceRequired');
    }

    public function buyerCountry(): string
    {
        return RequestValue::string($this->input('buyerCountry'));
    }

    public function buyerIsBusiness(): bool
    {
        return (bool) $this->input('buyerIsBusiness');
    }

    public function paidByCard(): bool
    {
        return (bool) $this->input('paidByCard');
    }

    public function registrationNumber(): ?string
    {
        $value = $this->input('registrationNumber');

        return $value !== null ? RequestValue::string($value) : null;
    }
}
