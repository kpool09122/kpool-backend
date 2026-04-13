<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Billing\Command\CreateInvoice;

use Application\Http\Action\Concerns\ResolvesLanguage;
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
        return (string) $this->input('orderIdentifier');
    }

    public function buyerMonetizationAccountIdentifier(): string
    {
        return (string) $this->input('buyerMonetizationAccountIdentifier');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lines(): array
    {
        return (array) $this->input('lines', []);
    }

    public function shippingCostAmount(): int
    {
        return (int) $this->input('shippingCostAmount');
    }

    public function currency(): string
    {
        return (string) $this->input('currency');
    }

    public function discountPercentage(): ?int
    {
        $value = $this->input('discountPercentage');

        return $value !== null ? (int) $value : null;
    }

    public function discountCode(): ?string
    {
        $value = $this->input('discountCode');

        return $value !== null ? (string) $value : null;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function taxLines(): ?array
    {
        $value = $this->input('taxLines');

        return $value !== null ? (array) $value : null;
    }

    public function sellerCountry(): string
    {
        return (string) $this->input('sellerCountry');
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
        return (string) $this->input('buyerCountry');
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

        return $value !== null ? (string) $value : null;
    }
}
