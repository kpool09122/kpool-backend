<?php

declare(strict_types=1);

namespace Source\Account\Policy\Infrastructure\Service;

use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\Policy\Domain\Repository\AccountPolicyRepositoryInterface;
use Source\Account\Policy\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\AccountResource;
use Source\Account\Policy\Domain\ValueObject\AccountResourceType;
use Source\Account\Policy\Domain\ValueObject\Effect;
use Source\Account\Policy\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PolicyEvaluator implements PolicyEvaluatorInterface
{
    public function __construct(
        private IdentityGroupRepositoryInterface $identityGroupRepository,
        private AccountPolicyRepositoryInterface $accountPolicyRepository,
    ) {
    }

    public function evaluate(
        IdentityIdentifier $actorIdentityIdentifier,
        AccountAction $action,
        AccountResource $resource,
    ): bool {
        $statements = $this->collectStatements($actorIdentityIdentifier, $resource);
        $applicableStatements = $this->filterApplicable($statements, $action, $resource->type());

        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::DENY) {
                return false;
            }
        }

        foreach ($applicableStatements as $statement) {
            if ($statement->effect() === Effect::ALLOW) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Statement[]
     */
    private function collectStatements(IdentityIdentifier $actorIdentityIdentifier, AccountResource $resource): array
    {
        $identityGroups = $this->identityGroupRepository->findByAccountIdAndIdentityId(
            $resource->accountIdentifier(),
            $actorIdentityIdentifier,
        );

        if (empty($identityGroups)) {
            return [];
        }

        $roles = [];
        foreach ($identityGroups as $identityGroup) {
            $roles[$identityGroup->role()->value] = $identityGroup->role();
        }

        $policies = $this->accountPolicyRepository->findByRoles(array_values($roles));

        $statements = [];
        foreach ($policies as $policy) {
            array_push($statements, ...$policy->statements());
        }

        return $statements;
    }

    /**
     * @param Statement[] $statements
     * @return Statement[]
     */
    private function filterApplicable(array $statements, AccountAction $action, AccountResourceType $resourceType): array
    {
        return array_filter($statements, static function (Statement $statement) use ($action, $resourceType): bool {
            $actionMatches = in_array($action, $statement->actions(), true);
            $resourceMatches = in_array($resourceType, $statement->resourceTypes(), true);

            return $actionMatches && $resourceMatches;
        });
    }
}
