<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency\AutoCreateAgencyInput;
use Source\Wiki\Agency\Domain\ValueObject\AutoAgencyCreationPayload;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Tests\Helper\StrTestHelper;

class AutoCreateAgencyInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutoAgencyCreationPayload(
            Language::KOREAN,
            new Name('JYP엔터테인먼트'),
        );
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutoCreateAgencyInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
