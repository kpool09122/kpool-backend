<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\ListAccountCategoryChangeRequests;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class ListAccountCategoryChangeRequestsRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string'],
            'requestedAccountCategory' => ['nullable', 'string'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function status(): ?string
    {
        return $this->query('status') !== null ? (string) $this->query('status') : null;
    }

    public function requestedAccountCategory(): ?string
    {
        return $this->query('requestedAccountCategory') !== null ? (string) $this->query('requestedAccountCategory') : null;
    }

    public function perPage(): ?int
    {
        return $this->query('perPage') !== null ? (int) $this->query('perPage') : null;
    }

    public function page(): int
    {
        return $this->query('page') !== null ? (int) $this->query('page') : 1;
    }
}
