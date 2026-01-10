<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy;

use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemPolicyException;
use Source\Wiki\Principal\Application\Exception\PolicyNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;

readonly class DeletePolicy implements DeletePolicyInterface
{
    public function __construct(
        private PolicyRepositoryInterface $policyRepository,
    ) {
    }

    /**
     * @throws PolicyNotFoundException
     * @throws CannotDeleteSystemPolicyException
     */
    public function process(DeletePolicyInputPort $input): void
    {
        $policy = $this->policyRepository->findById($input->policyIdentifier());

        if ($policy === null) {
            throw new PolicyNotFoundException();
        }

        if ($policy->isSystemPolicy()) {
            throw new CannotDeleteSystemPolicyException();
        }

        $this->policyRepository->delete($policy);
    }
}
