<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListDraftWikis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ListDraftWikisRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'translationSetIdentifier' => ['nullable', 'uuid'],
            'status' => ['required', 'string', Rule::in(array_column(ApprovalStatus::cases(), 'value'))],
            'onlyMine' => ['nullable', 'boolean'],
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

    public function status(): string
    {
        return (string) $this->query('status');
    }

    public function onlyMine(): bool
    {
        return $this->boolean('onlyMine');
    }

    public function resourceType(): ?string
    {
        $resourceType = $this->query('resourceType');

        return $resourceType === null ? null : (string) $resourceType;
    }
}
