<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\SearchTranslationSetMasterWikis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class SearchTranslationSetMasterWikisRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'resourceType' => ['required', 'string', Rule::in([
                ResourceType::AGENCY->value,
                ResourceType::GROUP->value,
                ResourceType::TALENT->value,
                ResourceType::SONG->value,
            ])],
            'keyword' => ['required', 'string', 'filled'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function resourceType(): string
    {
        return (string) $this->query('resourceType');
    }

    public function keyword(): string
    {
        return (string) $this->query('keyword');
    }

    public function limit(): ?int
    {
        $limit = $this->query('limit');

        return $limit === null ? null : (int) $limit;
    }
}
