<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Settlement\Command\SettleRevenue;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class SettleRevenueRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'settlementScheduleId' => ['required', 'uuid'],
            'paidAmounts' => ['required', 'array'],
            'paidAmounts.*.amount' => ['required', 'integer', 'min:0'],
            'paidAmounts.*.currency' => ['required', 'string'],
            'gatewayFeeRate' => ['required', 'integer', 'min:0', 'max:100'],
            'platformFeeRate' => ['required', 'integer', 'min:0', 'max:100'],
            'fixedFeeAmount' => ['nullable', 'integer', 'min:0'],
            'fixedFeeCurrency' => ['nullable', 'string', 'required_with:fixedFeeAmount'],
            'periodStart' => ['required', 'date'],
            'periodEnd' => ['required', 'date'],
        ];
    }

    public function settlementScheduleId(): string
    {
        return (string) $this->input('settlementScheduleId');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paidAmounts(): array
    {
        return (array) $this->input('paidAmounts', []);
    }

    public function gatewayFeeRate(): int
    {
        return (int) $this->input('gatewayFeeRate');
    }

    public function platformFeeRate(): int
    {
        return (int) $this->input('platformFeeRate');
    }

    public function fixedFeeAmount(): ?int
    {
        $value = $this->input('fixedFeeAmount');

        return $value !== null ? (int) $value : null;
    }

    public function fixedFeeCurrency(): ?string
    {
        $value = $this->input('fixedFeeCurrency');

        return $value !== null ? (string) $value : null;
    }

    public function periodStart(): string
    {
        return (string) $this->input('periodStart');
    }

    public function periodEnd(): string
    {
        return (string) $this->input('periodEnd');
    }
}
