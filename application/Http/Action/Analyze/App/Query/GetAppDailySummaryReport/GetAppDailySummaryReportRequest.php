<?php

declare(strict_types=1);

namespace Application\Http\Action\Analyze\App\Query\GetAppDailySummaryReport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Shared\Domain\ValueObject\CountryCode;

class GetAppDailySummaryReportRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
            'country' => ['nullable', Rule::in([...array_column(CountryCode::cases(), 'value'), 'unknown'])],
        ];
    }

    public function country(): ?string
    {
        $value = $this->query('country');

        return is_string($value) ? $value : null;
    }

    public function from(): string
    {
        return (string) $this->query('from');
    }

    public function to(): string
    {
        return (string) $this->query('to');
    }
}
