<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki\AutoCreateWikiInput;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class AutoCreateWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $payload = new AutoWikiCreationPayload(
            Language::KOREAN,
            ResourceType::GROUP,
            new Name('TWICE'),
            null,
            [],
            [],
        );

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutoCreateWikiInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }

    public function testWithAllParameters(): void
    {
        $agencyIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new WikiIdentifier(StrTestHelper::generateUuid()),
        ];
        $talentIdentifiers = [
            new WikiIdentifier(StrTestHelper::generateUuid()),
        ];

        $payload = new AutoWikiCreationPayload(
            Language::KOREAN,
            ResourceType::GROUP,
            new Name('TWICE'),
            $agencyIdentifier,
            $groupIdentifiers,
            $talentIdentifiers,
        );

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutoCreateWikiInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($agencyIdentifier, $input->payload()->agencyIdentifier());
        $this->assertSame($groupIdentifiers, $input->payload()->groupIdentifiers());
        $this->assertSame($talentIdentifiers, $input->payload()->talentIdentifiers());
    }
}
