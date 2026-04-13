<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\RequestAffiliation;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class RequestAffiliationRequest extends FormRequest
{
    use ResolvesLanguage;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'agencyAccountIdentifier' => ['required', 'uuid'],
            'talentAccountIdentifier' => ['required', 'uuid'],
            'requestedBy' => ['required', 'uuid'],
            'terms' => ['nullable', 'array'],
            'terms.revenueSharePercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'terms.contractNotes' => ['nullable', 'string'],
        ];
    }

    public function agencyAccountIdentifier(): string
    {
        return (string) $this->input('agencyAccountIdentifier');
    }

    public function talentAccountIdentifier(): string
    {
        return (string) $this->input('talentAccountIdentifier');
    }

    public function requestedBy(): string
    {
        return (string) $this->input('requestedBy');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function terms(): ?array
    {
        $value = $this->input('terms');

        return $value !== null ? (array) $value : null;
    }
}
