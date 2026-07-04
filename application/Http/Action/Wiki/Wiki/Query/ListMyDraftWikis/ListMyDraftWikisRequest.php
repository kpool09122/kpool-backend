<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListMyDraftWikis;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ListMyDraftWikisRequest extends FormRequest
{
    use ResolvesLanguage;

    protected function prepareForValidation(): void
    {
        $statuses = $this->query('statuses');

        if (is_string($statuses)) {
            $this->merge([
                'statuses' => array_values(array_filter(
                    explode(',', $statuses),
                    static fn (string $status): bool => $status !== '',
                )),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'translationSetIdentifier' => ['nullable', 'uuid'],
            'statuses' => ['required', 'array', 'min:1'],
            'statuses.*' => ['required', 'string', Rule::in(array_column(ApprovalStatus::cases(), 'value'))],
            'resourceType' => ['nullable', 'string', Rule::in([
                ResourceType::AGENCY->value,
                ResourceType::GROUP->value,
                ResourceType::TALENT->value,
                ResourceType::SONG->value,
            ])],
        ];
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }

    public function translationSetIdentifier(): ?string
    {
        $translationSetIdentifier = $this->query('translationSetIdentifier');

        return $translationSetIdentifier === null ? null : (string) $translationSetIdentifier;
    }

    /**
     * @return string[]
     */
    public function statuses(): array
    {
        /** @var string[] $statuses */
        $statuses = $this->input('statuses');

        return $statuses;
    }

    public function resourceType(): ?string
    {
        $resourceType = $this->query('resourceType');

        return $resourceType === null ? null : (string) $resourceType;
    }
}
