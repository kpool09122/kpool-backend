<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\ListContacts;

use Illuminate\Foundation\Http\FormRequest;

class ListContactsRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identityIdentifier' => ['nullable', 'uuid'],
            'hasReply' => ['nullable', 'string', 'in:0,1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function identityIdentifier(): ?string
    {
        return $this->query('identityIdentifier') !== null ? (string) $this->query('identityIdentifier') : null;
    }

    public function hasReply(): ?bool
    {
        return $this->query('hasReply') !== null ? $this->boolean('hasReply') : null;
    }

    public function perPage(): ?int
    {
        $perPage = $this->query('perPage');

        return $perPage === null ? null : (int) $perPage;
    }

    public function page(): int
    {
        return $this->query('page') !== null ? (int) $this->query('page') : 1;
    }
}
