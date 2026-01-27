<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\TranslateTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent\TranslateTalentInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateTalentInputTest extends TestCase
{
    public function test__construct(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateTalentInput(
            $talentIdentifier,
            $principalIdentifier,
        );

        $this->assertSame((string) $talentIdentifier, (string) $input->talentIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertNull($input->publishedTalentIdentifier());
    }

    public function testWithPublishedTalentIdentifier(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateTalentInput(
            $talentIdentifier,
            $principalIdentifier,
            $publishedTalentIdentifier,
        );

        $this->assertSame((string) $talentIdentifier, (string) $input->talentIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($publishedTalentIdentifier, $input->publishedTalentIdentifier());
    }
}
