<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\TranslateTalent;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalentInput;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateTalentInputTest extends TestCase
{
    public function test__construct(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateTalentInput(
            $talentIdentifier,
            $principal,
        );

        $this->assertSame((string)$talentIdentifier, (string)$input->talentIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
