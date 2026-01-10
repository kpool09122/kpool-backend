<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy;

use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

readonly class DeletePolicyInput implements DeletePolicyInputPort
{
    public function __construct(
        private PolicyIdentifier $policyIdentifier,
    ) {
    }

    public function policyIdentifier(): PolicyIdentifier
    {
        return $this->policyIdentifier;
    }
}
