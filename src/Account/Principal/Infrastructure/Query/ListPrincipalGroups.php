<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Query;

use Application\Models\Account\PrincipalGroup as PrincipalGroupModel;
use Illuminate\Database\Eloquent\Collection;
use Source\Account\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInputPort;
use Source\Account\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Account\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Source\Account\Principal\Infrastructure\Query\Authorization\PrincipalGroupManageAuthorization;

readonly class ListPrincipalGroups implements ListPrincipalGroupsInterface
{
    public function __construct(private PrincipalGroupManageAuthorization $authorization)
    {
    }

    /** @return array<int, PrincipalGroupReadModel> */
    public function process(ListPrincipalGroupsInputPort $input): array
    {
        $accountIdentifier = $input->accountIdentifier();
        $this->authorization->assertAllowed($accountIdentifier, $input->principal());

        /** @var Collection<int, PrincipalGroupModel> $groups */
        $groups = PrincipalGroupModel::query()
            ->with(['members', 'roleAttachments'])
            ->where('account_id', (string) $accountIdentifier)
            ->orderBy('created_at')
            ->get();

        return $groups->map(static fn (PrincipalGroupModel $group): PrincipalGroupReadModel => new PrincipalGroupReadModel(
            principalGroupIdentifier: $group->id,
            accountIdentifier: $group->account_id,
            name: $group->name,
            roleIdentifiers: $group->roleAttachments->map(static fn ($attachment): string => $attachment->role_id)->values()->all(),
            isDefault: $group->is_default,
            members: $group->members->map(static fn ($member): string => $member->principal_id)->values()->all(),
        ))->values()->all();
    }
}
