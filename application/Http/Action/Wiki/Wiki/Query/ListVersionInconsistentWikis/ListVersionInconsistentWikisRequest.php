<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListVersionInconsistentWikis;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class ListVersionInconsistentWikisRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'resourceType' => ['nullable', 'string', Rule::in([
                ResourceType::AGENCY->value,
                ResourceType::GROUP->value,
                ResourceType::TALENT->value,
                ResourceType::SONG->value,
            ])],
            'sort' => ['nullable', 'string', Rule::in(['updatedAt', 'name'])],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
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
