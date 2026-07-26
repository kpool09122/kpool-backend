<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\PrincipalGroup\Query\ListPrincipalGroups;

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
        return [];
    }

    public function language(): string
    {
        return (string) $this->header('Accept-Language', (string) config('app.fallback_locale'));
    }
}
