<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;

readonly class CreatePolicy implements CreatePolicyInterface
{
    public function __construct(
        private PolicyRepositoryInterface $policyRepository,
        private PolicyFactoryInterface $policyFactory,
    ) {
    }

    public function process(CreatePolicyInputPort $input): Policy
    {
        $policy = $this->policyFactory->create(
            $input->name(),
            $input->statements(),
            $input->isSystemPolicy(),
        );

        $this->policyRepository->save($policy);

        return $policy;
    }
}
