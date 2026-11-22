<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\Service;

use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;

readonly class PrincipalGroupManagerContinuity
{
    public function __construct(private RoleRepositoryInterface $roleRepository, private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    /** @param array<PrincipalGroup> $principalGroups
     * @param array<string, Principal> $principalsById
     * @param callable(Principal): bool $isReachable */
    public function hasManager(array $principalGroups, array $principalsById, callable $isReachable): bool
    {
        $administrator = $this->roleRepository->findSystemByName('Owner');
        if ($administrator === null) {
            return false;
        }
        foreach ($principalGroups as $group) {
            if (! $group->hasRole($administrator->roleIdentifier())) {
                continue;
            }
            foreach ($group->members() as $identifier) {
                $principal = $principalsById[(string) $identifier] ?? null;
                if ($principal !== null && $principal->status() === PrincipalStatus::ACTIVE
                    && (string) $principal->accountIdentifier() === (string) $group->accountIdentifier()
                    && $isReachable($principal)
                    && $this->policyEvaluator->evaluate($principal, Action::PRINCIPAL_GROUP_MANAGE, new Resource(ResourceType::PRINCIPAL_GROUP, $group->accountIdentifier()), $principalGroups)) {
                    return true;
                }
            }
        }

        return false;
    }
}
