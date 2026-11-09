<?php

declare(strict_types=1);

namespace Application\Http\Action\Analyze\App\Query\GetAppRegionalDailySummaryReport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Shared\Domain\ValueObject\AdministrativeAreaCode;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

class GetAppRegionalDailySummaryReportRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
            'country' => ['required', Rule::enum(CountryCode::class)],
            'region' => ['nullable', 'string', Rule::in(['unknown', ...array_filter(array_unique([...array_keys(SubdivisionCode::NAMES), ...array_column(AdministrativeAreaCode::cases(), 'value')]), fn (string $code): bool => str_starts_with($code, $this->country() . '-'))])],
        ];
    }

    public function from(): string
    {
        return (string) $this->query('from');
    }

    public function to(): string
    {
        return (string) $this->query('to');
    }

    public function region(): ?string
    {
        $value = $this->query('region');

        return is_string($value) ? $value : null;
    }

    public function country(): string
    {
        $value = $this->query('country');

        return is_string($value) ? $value : '';
    }
}
