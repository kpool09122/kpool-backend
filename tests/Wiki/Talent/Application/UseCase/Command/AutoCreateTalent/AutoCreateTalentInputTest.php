<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent\AutoCreateTalentInput;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutoTalentCreationPayload;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;

class AutoCreateTalentInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutoTalentCreationPayload(
            Language::JAPANESE,
            new TalentName('自動作成タレント'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new GroupIdentifier(StrTestHelper::generateUuid())],
        );
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutoCreateTalentInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
