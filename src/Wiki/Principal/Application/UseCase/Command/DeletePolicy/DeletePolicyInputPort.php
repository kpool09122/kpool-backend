<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy;

use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

interface DeletePolicyInputPort
{
    public function policyIdentifier(): PolicyIdentifier;
}
