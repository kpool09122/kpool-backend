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
        ];
    }

    public function identityIdentifier(): ?string
    {
        return $this->query('identityIdentifier') !== null ? (string) $this->query('identityIdentifier') : null;
    }
}
