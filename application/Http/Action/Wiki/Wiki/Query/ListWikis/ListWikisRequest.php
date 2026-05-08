<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListWikis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ListWikisRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'language' => $this->route('language'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'language' => ['required', 'string', 'in:ja,ko,en'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'resourceType' => ['nullable', 'string', Rule::in([
                ResourceType::AGENCY->value,
                ResourceType::GROUP->value,
                ResourceType::TALENT->value,
                ResourceType::SONG->value,
            ])],
            'keyword' => ['nullable', 'string'],
            'sort' => ['nullable', 'string', Rule::in(['updatedAt', 'name'])],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function language(): string
    {
        return (string) $this->route('language');
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }

    public function resourceType(): ?string
    {
        $resourceType = $this->query('resourceType');

        return $resourceType === null ? null : (string) $resourceType;
    }

    public function keyword(): ?string
    {
        $keyword = $this->query('keyword');

        return $keyword === null ? null : (string) $keyword;
    }

    public function sort(): ?string
    {
        $sort = $this->query('sort');

        return $sort === null ? null : (string) $sort;
    }

    public function order(): ?string
    {
        $order = $this->query('order');

        return $order === null ? null : (string) $order;
    }
}
