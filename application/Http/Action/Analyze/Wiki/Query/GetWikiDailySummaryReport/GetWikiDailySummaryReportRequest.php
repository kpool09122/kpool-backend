<?php

declare(strict_types=1);

namespace Application\Http\Action\Analyze\Wiki\Query\GetWikiDailySummaryReport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;

class GetWikiDailySummaryReportRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
            'wikiIdentifier' => ['nullable', 'uuid', 'prohibits:translationSetIdentifier'],
            'translationSetIdentifier' => ['nullable', 'uuid', 'prohibits:wikiIdentifier'],
            'language' => ['nullable', Rule::enum(Language::class)],
            'country' => ['nullable', Rule::in([...array_column(CountryCode::cases(), 'value'), 'unknown'])],
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

    public function wikiIdentifier(): ?string
    {
        $value = $this->query('wikiIdentifier');

        return $value === null ? null : (string) $value;
    }

    public function translationSetIdentifier(): ?string
    {
        $value = $this->query('translationSetIdentifier');

        return $value === null ? null : (string) $value;
    }

    public function language(): ?string
    {
        $value = $this->query('language');

        return $value === null ? null : (string) $value;
    }

    public function country(): ?string
    {
        $value = $this->query('country');

        return $value === null ? null : (string) $value;
    }
}
