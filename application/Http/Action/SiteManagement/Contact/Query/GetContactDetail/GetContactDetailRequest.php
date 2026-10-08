<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail;

use Illuminate\Foundation\Http\FormRequest;

class GetContactDetailRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['principalIdentifier' => ['required', 'uuid'], 'contactIdentifier' => ['required', 'uuid']];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['principalIdentifier' => $this->route('principalIdentifier'), 'contactIdentifier' => $this->route('contactIdentifier')]);
    }

    public function principalIdentifier(): string
    {
        return (string) $this->route('principalIdentifier');
    }

    public function contactIdentifier(): string
    {
        return (string) $this->route('contactIdentifier');
    }
}
