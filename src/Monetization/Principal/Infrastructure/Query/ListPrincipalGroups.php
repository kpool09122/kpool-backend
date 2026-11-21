<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Query;

use Application\Models\Monetization\PrincipalGroup as PrincipalGroupModel;
use Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInputPort;
use Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Monetization\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Source\Monetization\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use UnexpectedValueException;

readonly class ListPrincipalGroups implements ListPrincipalGroupsInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @return array<int, PrincipalGroupReadModel>
     * @throws UnauthorizedException
     */
    public function process(ListPrincipalGroupsInputPort $input): array
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate(
            $principal,
            Action::PRINCIPAL_GROUP_MANAGE,
            new Resource(ResourceType::PRINCIPAL_GROUP, $input->accountIdentifier()),
        )) {
            throw new UnauthorizedException();
        }

        $accountId = (string) $input->accountIdentifier();

        return PrincipalGroupModel::query()
            ->where('account_id', $accountId)
            ->with([
                'memberships' => static fn ($query) => $query
                    ->whereHas('principal', static fn ($query) => $query->where('account_id', $accountId))
                    ->orderBy('principal_id'),
                'memberships.principal.identity:id,identity_name,email',
                'roleAttachments' => static fn ($query) => $query
                    ->whereHas('role', static fn ($query) => $query->whereNull('account_id')->orWhere('account_id', $accountId))
                    ->orderBy('role_id'),
            ])
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->map(static fn (PrincipalGroupModel $group): PrincipalGroupReadModel => new PrincipalGroupReadModel(
                principalGroupIdentifier: $group->id,
                accountIdentifier: $group->account_id,
                name: $group->name,
                description: $group->description,
                roleIdentifiers: $group->roleAttachments->map(static fn ($attachment): string => $attachment->role_id)->values()->all(),
                isDefault: $group->is_default,
                members: $group->memberships->map(static function ($membership): PrincipalGroupMemberReadModel {
                    $member = $membership->principal ?? throw new UnexpectedValueException('Missing membership principal.');
                    $identity = $member->identity ?? throw new UnexpectedValueException('Missing principal identity.');

                    return new PrincipalGroupMemberReadModel(
                        principalIdentifier: $member->id,
                        status: $member->status,
                        identityIdentifier: $member->identity_id,
                        identityName: $identity->identity_name,
                        email: $identity->email,
                    );
                })->values()->all(),
            ))->all();
    }
}
