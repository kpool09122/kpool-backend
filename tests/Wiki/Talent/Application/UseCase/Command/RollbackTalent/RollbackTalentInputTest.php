<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\RollbackTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Application\UseCase\Command\RollbackTalent\RollbackTalentInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RollbackTalentInputTest extends TestCase
{
    public function testConstruct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $targetVersion = new Version(3);

        $input = new RollbackTalentInput(
            $principalIdentifier,
            $talentIdentifier,
            $targetVersion,
        );

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($talentIdentifier, $input->talentIdentifier());
        $this->assertSame($targetVersion, $input->targetVersion());
    }
}
