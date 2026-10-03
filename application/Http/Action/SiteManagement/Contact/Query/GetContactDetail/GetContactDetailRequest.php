<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail;

use Illuminate\Foundation\Http\FormRequest;

class GetContactDetailRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['identityIdentifier' => ['required', 'uuid'], 'contactIdentifier' => ['required', 'uuid']];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['identityIdentifier' => $this->route('identityIdentifier'), 'contactIdentifier' => $this->route('contactIdentifier')]);
    }

    public function identityIdentifier(): string
    {
        return (string) $this->route('identityIdentifier');
    }

    public function contactIdentifier(): string
    {
        return (string) $this->route('contactIdentifier');
    }
}
