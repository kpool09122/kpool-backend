<?php

declare(strict_types=1);

namespace Application\Http\Action\Analyze\App\Query\GetAppDailySummaryReport;

use Illuminate\Foundation\Http\FormRequest;

class GetAppDailySummaryReportRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
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
}
