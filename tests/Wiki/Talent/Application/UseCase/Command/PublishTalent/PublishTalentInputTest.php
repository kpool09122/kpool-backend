<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\PublishTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Application\UseCase\Command\PublishTalent\PublishTalentInput;
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
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new PublishTalentInput(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $principalIdentifier,
        );
        $this->assertSame((string)$talentIdentifier, (string)$input->talentIdentifier());
        $this->assertSame((string)$publishedTalentIdentifier, (string)$input->publishedTalentIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
