<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RollbackTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

interface RollbackTalentInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function talentIdentifier(): TalentIdentifier;

    public function targetVersion(): Version;
}
