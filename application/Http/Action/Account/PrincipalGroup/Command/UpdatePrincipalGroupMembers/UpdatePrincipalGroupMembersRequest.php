<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\PrincipalGroup\Command\UpdatePrincipalGroupMembers;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePrincipalGroupMembersRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'principalGroups' => ['required', 'array'],
            'principalGroups.*.principalGroupIdentifier' => ['required', 'uuid', 'distinct'],
            'principalGroups.*.principalIdentifiers' => ['present', 'array'],
            'principalGroups.*.principalIdentifiers.*' => ['required', 'uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int, mixed> $principalGroups */
            $principalGroups = $this->input('principalGroups', []);

            foreach ($principalGroups as $principalGroupIndex => $principalGroup) {
                if (! is_array($principalGroup) || ! isset($principalGroup['principalIdentifiers']) || ! is_array($principalGroup['principalIdentifiers'])) {
                    continue;
                }

                $seen = [];
                foreach ($principalGroup['principalIdentifiers'] as $principalIdentifierIndex => $principalIdentifier) {
                    if (! is_string($principalIdentifier)) {
                        continue;
                    }

                    if (isset($seen[$principalIdentifier])) {
                        $validator->errors()->add(
                            "principalGroups.{$principalGroupIndex}.principalIdentifiers.{$principalIdentifierIndex}",
                            __('validation.distinct', ['attribute' => "principalGroups.{$principalGroupIndex}.principalIdentifiers.{$principalIdentifierIndex}"]),
                        );
                    }

                    $seen[$principalIdentifier] = true;
                }
            }
        });
    }

    /** @return array<int, array{principalGroupIdentifier: string, principalIdentifiers: array<int, string>}> */
    public function principalGroups(): array
    {
        /** @var array<int, array{principalGroupIdentifier: string, principalIdentifiers: array<int, string>}> $principalGroups */
        $principalGroups = $this->input('principalGroups', []);

        return $principalGroups;
    }
}
