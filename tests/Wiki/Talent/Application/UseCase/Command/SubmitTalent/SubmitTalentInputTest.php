<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\SubmitTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent\SubmitTalentInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitTalentInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new SubmitTalentInput(
            $talentIdentifier,
            $principalIdentifier,
        );

        $this->assertSame((string) $talentIdentifier, (string) $input->talentIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
