<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Query;

use Application\Models\Account\Principal as PrincipalModel;
use Illuminate\Database\Eloquent\Collection;
use Source\Account\Principal\Application\UseCase\Query\ListMembers\ListMembersInputPort;
use Source\Account\Principal\Application\UseCase\Query\ListMembers\ListMembersInterface;
use Source\Account\Principal\Application\UseCase\Query\MemberPrincipalGroupReadModel;
use Source\Account\Principal\Application\UseCase\Query\MemberReadModel;
use Source\Account\Principal\Infrastructure\Query\Authorization\PrincipalGroupManageAuthorization;

readonly class ListMembers implements ListMembersInterface
{
    public function __construct(private PrincipalGroupManageAuthorization $authorization)
    {
    }

    /** @return array<int, MemberReadModel> */
    public function process(ListMembersInputPort $input): array
    {
        $accountIdentifier = $input->accountIdentifier();
        $this->authorization->assertAllowed($accountIdentifier, $input->principal(), $input->accountType());

        /** @var Collection<int, PrincipalModel> $principals */
        $principals = PrincipalModel::query()
            ->select('account_principals.*')
            ->with(['identity', 'principalGroupMemberships.principalGroup'])
            ->where('account_id', (string) $accountIdentifier)
            ->join('identities', 'identities.id', '=', 'account_principals.identity_id')
            ->orderBy('identities.identity_name')
            ->get();

        return $principals->map(static fn (PrincipalModel $principal): MemberReadModel => new MemberReadModel(
            principalIdentifier: $principal->id,
            identityIdentifier: $principal->identity_id,
            identityName: $principal->identity->identity_name,
            email: $principal->identity->email,
            principalGroups: $principal->principalGroupMemberships
                ->map(static function ($membership): ?MemberPrincipalGroupReadModel {
                    $principalGroup = $membership->principalGroup;
                    if ($principalGroup === null) {
                        return null;
                    }

                    return new MemberPrincipalGroupReadModel($principalGroup->id, $principalGroup->name, $principalGroup->is_default);
                })
                ->filter()
                ->values()
                ->all(),
        ))->values()->all();
    }
}
