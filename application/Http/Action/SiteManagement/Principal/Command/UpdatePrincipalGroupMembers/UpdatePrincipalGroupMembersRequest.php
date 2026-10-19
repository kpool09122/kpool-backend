<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Principal\Command\UpdatePrincipalGroupMembers;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrincipalGroupMembersRequest extends FormRequest
{
    use ResolvesLanguage;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'principalGroups' => ['present', 'array'],
            'principalStatuses' => ['sometimes', 'array'],
            'principalStatuses.*.principalIdentifier' => ['required', 'uuid', 'distinct'],
            'principalStatuses.*.status' => ['required', 'in:active,suspended,forced_stop'],
            'principalGroups.*.principalGroupIdentifier' => ['required', 'uuid', 'distinct'],
            'principalGroups.*.principalIdentifiers' => ['present', 'array', 'list'],
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

    /** @return array<int, array{principalIdentifier: string, status: string}> */
    public function principalStatuses(): array
    {
        /** @var array<int, array{principalIdentifier: string, status: string}> $statuses */
        $statuses = $this->validated('principalStatuses', []);

        return $statuses;
    }
}
