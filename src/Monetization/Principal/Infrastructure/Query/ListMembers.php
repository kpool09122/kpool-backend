<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Query;

use Application\Models\Monetization\Principal as PrincipalModel;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInputPort;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInterface;
use Source\Monetization\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use UnexpectedValueException;

readonly class ListMembers implements ListMembersInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @return array<int, PrincipalGroupMemberReadModel>
     * @throws UnauthorizedException
     */
    public function process(ListMembersInputPort $input): array
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate(
            $principal,
            Action::PRINCIPAL_GROUP_MANAGE,
            new Resource(ResourceType::PRINCIPAL_GROUP, $input->accountIdentifier()),
        )) {
            throw new UnauthorizedException();
        }

        return PrincipalModel::query()
            ->where('account_id', (string) $input->accountIdentifier())
            ->with('identity:id,identity_name,email')
            ->orderBy('id')
            ->get()
            ->map(static function (PrincipalModel $member): PrincipalGroupMemberReadModel {
                $identity = $member->identity ?? throw new UnexpectedValueException('Missing principal identity.');

                return new PrincipalGroupMemberReadModel(
                    principalIdentifier: $member->id,
                    status: $member->status,
                    identityIdentifier: $member->identity_id,
                    identityName: $identity->identity_name,
                    email: $identity->email,
                );
            })->all();
    }
}
