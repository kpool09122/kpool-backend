<?php

declare(strict_types=1);

namespace Application\Http\Action\Analyze\Wiki\Query\GetWikiRegionalDailySummaryReport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Shared\Domain\ValueObject\AdministrativeAreaCode;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

class GetWikiRegionalDailySummaryReportRequest extends FormRequest
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
