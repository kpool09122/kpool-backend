<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\ListContactsByPrincipal;

use Illuminate\Foundation\Http\FormRequest;

class ListContactsByPrincipalRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'principalIdentifier' => ['required', 'uuid'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'principalIdentifier' => $this->route('principalIdentifier'),
        ]);
    }

    public function principalIdentifier(): string
    {
        return (string) $this->route('principalIdentifier');
    }
}
