<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\GetContactDetailByIdentifier;

use Illuminate\Foundation\Http\FormRequest;

class GetContactDetailByIdentifierRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['contactIdentifier' => ['required', 'uuid']];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['contactIdentifier' => $this->route('contactIdentifier')]);
    }

    public function contactIdentifier(): string
    {
        return (string) $this->route('contactIdentifier');
    }
}
