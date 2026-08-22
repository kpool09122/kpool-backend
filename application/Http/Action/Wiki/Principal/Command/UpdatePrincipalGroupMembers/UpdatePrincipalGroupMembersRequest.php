<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\UpdatePrincipalGroupMembers;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

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

    /** @return array<int, array{principalGroupIdentifier: string, principalIdentifiers: array<int, string>}> */
    public function principalGroups(): array
    {
        /** @var array<int, array{principalGroupIdentifier: string, principalIdentifiers: array<int, string>}> $principalGroups */
        $principalGroups = $this->input('principalGroups', []);

        return $principalGroups;
    }
}
