<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\RejectTalent;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Application\UseCase\Command\RejectTalent\RejectTalentInput;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectTalentInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());

        $input = new RejectTalentInput(
            $talentIdentifier,
            $principalIdentifier,
        );
        $this->assertSame((string)$talentIdentifier, (string)$input->talentIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
