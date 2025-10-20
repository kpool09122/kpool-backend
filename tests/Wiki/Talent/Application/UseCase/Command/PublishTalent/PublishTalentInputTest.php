<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\PublishTalent;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalentInput;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishTalentInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principal,
        );
        $this->assertSame((string)$talentIdentifier, (string)$input->talentIdentifier());
        $this->assertSame((string)$publishedTalentIdentifier, (string)$input->publishedTalentIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
