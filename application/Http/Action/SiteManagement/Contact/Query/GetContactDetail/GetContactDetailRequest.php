<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;

class GetContactDetailRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $route = $this->route();
        $requiresPrincipalIdentifier = $route instanceof Route && in_array('principalIdentifier', $route->parameterNames(), true);

        return [
            'principalIdentifier' => [$requiresPrincipalIdentifier ? 'required' : 'nullable', 'uuid'],
            'contactIdentifier' => ['required', 'uuid'],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return [
            'principalIdentifier' => $this->route('principalIdentifier'),
            'contactIdentifier' => $this->route('contactIdentifier'),
        ];
    }

    public function principalIdentifier(): ?string
    {
        $principalIdentifier = $this->route('principalIdentifier');

        return $principalIdentifier === null ? null : (string) $principalIdentifier;
    }

    public function contactIdentifier(): string
    {
        return (string) $this->route('contactIdentifier');
    }
}
