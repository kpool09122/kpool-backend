<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\GetMyContactDetail;

use Illuminate\Foundation\Http\FormRequest;

class GetMyContactDetailRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['contactIdentifier' => ['required', 'uuid']];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['contactIdentifier' => $this->route('contactIdentifier')]);
    }

    public function contactIdentifier(): string
    {
        return (string) $this->route('contactIdentifier');
    }
}
