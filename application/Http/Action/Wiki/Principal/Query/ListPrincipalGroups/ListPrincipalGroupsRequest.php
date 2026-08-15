<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Query\ListPrincipalGroups;

use Illuminate\Foundation\Http\FormRequest;

class ListPrincipalGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'accountIdentifier' => ['required', 'uuid'],
        ];
    }

    public function accountIdentifier(): string
    {
        return (string) $this->query('accountIdentifier');
    }
}
