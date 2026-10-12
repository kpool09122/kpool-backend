<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Query;

use Application\Models\Wiki\PrincipalGroup as PrincipalGroupModel;
use Application\Models\Wiki\Role as RoleModel;
use Illuminate\Database\Eloquent\Collection;
use Source\Wiki\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInputPort;
use Source\Wiki\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Wiki\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Source\Wiki\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use UnexpectedValueException;

readonly class ListPrincipalGroups implements ListPrincipalGroupsInterface
{
    /** @return array<int, PrincipalGroupReadModel> */
    public function process(ListPrincipalGroupsInputPort $input): array
    {
        /** @var Collection<int, PrincipalGroupModel> $groups */
        $groups = PrincipalGroupModel::query()
            ->with(['memberships.principal.identity', 'roleAttachments'])
            ->where('account_id', (string) $input->accountIdentifier())
            ->whereDoesntHave('roleAttachments', static fn ($attachments) => $attachments->whereIn('role_id', RoleModel::query()->select('id')->whereNull('account_id')->where('name', 'Operator')))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $groups->map(static fn (PrincipalGroupModel $group): PrincipalGroupReadModel => new PrincipalGroupReadModel(
            principalGroupIdentifier: $group->id,
            accountIdentifier: $group->account_id,
            name: $group->name,
            description: $group->description,
            roleIdentifiers: $group->roleAttachments->map(static fn ($attachment): string => $attachment->role_id)->values()->all(),
            isDefault: $group->is_default,
            members: $group->memberships->map(static function ($membership): PrincipalGroupMemberReadModel {
                $principal = $membership->principal ?? throw new UnexpectedValueException('Missing membership principal.');
                $identity = $principal->identity ?? throw new UnexpectedValueException('Missing principal identity.');

                return new PrincipalGroupMemberReadModel(
                    principalIdentifier: $principal->id,
                    identityIdentifier: $principal->identity_id,
                    identityName: $identity->identity_name,
                    email: $identity->email,
                );
            })->values()->all(),
        ))->values()->all();
    }
}
