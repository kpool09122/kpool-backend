<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Query;

use Application\Models\SiteManagement\Principal as PrincipalModel;
use Application\Models\SiteManagement\PrincipalGroup as PrincipalGroupModel;
use Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInputPort;
use Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupListReadModel;
use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use UnexpectedValueException;

readonly class ListPrincipalGroups implements ListPrincipalGroupsInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /** @throws UnauthorizedException */
    public function process(ListPrincipalGroupsInputPort $input): PrincipalGroupListReadModel
    {
        $operator = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($operator === null || (string) $operator->accountIdentifier() !== (string) $input->accountIdentifier()
            || ! $this->policyEvaluator->evaluate($operator, Action::PRINCIPAL_GROUP_MANAGE, new Resource(ResourceType::PRINCIPAL_GROUP))) {
            throw new UnauthorizedException();
        }

        $accountIdentifier = $input->accountIdentifier();
        $operatorRole = $this->roleRepository->findSystemByName('Operator');
        $principals = PrincipalModel::query()->with('identity')->where('account_id', (string) $accountIdentifier)->orderBy('created_at')->orderBy('id')->get();
        $principalsById = [];
        foreach ($principals as $principal) {
            $identity = $principal->identity ?? throw new UnexpectedValueException('Missing principal identity.');
            $principalsById[$principal->id] = new PrincipalGroupMemberReadModel($principal->id, $principal->identity_id, $identity->identity_name, $identity->email);
        }
        $groupQuery = PrincipalGroupModel::query()->with(['memberships', 'roleAttachments'])->where('account_id', (string) $accountIdentifier);
        if ($operatorRole !== null) {
            $operatorRoleIdentifier = (string) $operatorRole->roleIdentifier();
            $groupQuery->whereDoesntHave('roleAttachments', static fn ($attachments) => $attachments->where('role_id', $operatorRoleIdentifier));
        }
        $groups = $groupQuery->orderBy('created_at')->orderBy('id')->get();
        $readModels = [];
        foreach ($groups as $group) {
            $members = [];
            foreach ($group->memberships as $membership) {
                if (isset($principalsById[$membership->principal_id])) {
                    $members[] = $principalsById[$membership->principal_id];
                }
            }
            usort($members, static fn (PrincipalGroupMemberReadModel $left, PrincipalGroupMemberReadModel $right): int => strcmp($left->principalIdentifier(), $right->principalIdentifier()));
            $roleIdentifiers = $group->roleAttachments->map(static fn ($attachment): string => $attachment->role_id)->values()->all();
            sort($roleIdentifiers);
            $readModels[] = new PrincipalGroupReadModel(
                $group->id,
                $group->account_id,
                $group->name,
                $roleIdentifiers,
                $group->is_default,
                $members
            );
        }

        return new PrincipalGroupListReadModel($readModels, array_values($principalsById));
    }
}
