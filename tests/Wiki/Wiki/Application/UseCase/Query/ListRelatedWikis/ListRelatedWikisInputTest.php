<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisInput;
use Tests\TestCase;

class ListRelatedWikisInputTest extends TestCase
{
    public function testConstructsWithAgencyOrTalentSource(): void
    {
        $input = new ListRelatedWikisInput(
            ResourceType::AGENCY,
            new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f001'),
            new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f002'),
            AccountCategory::GENERAL,
        );

        $this->assertSame(ResourceType::AGENCY, $input->resourceType());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f001', (string) $input->translationSetIdentifier());
        $this->assertSame(AccountCategory::GENERAL, $input->accountCategory());
    }

    public function testRejectsUnsupportedSourceResourceType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListRelatedWikisInput(
            ResourceType::GROUP,
            new TranslationSetIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f003'),
            new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f004'),
            AccountCategory::GENERAL,
        );
    }
}
