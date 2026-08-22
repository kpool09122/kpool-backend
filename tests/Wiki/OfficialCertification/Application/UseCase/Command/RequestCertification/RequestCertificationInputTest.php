<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInput;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class RequestCertificationInputTest extends TestCase
{
    public function test__construct(): void
    {
        $resourceType = ResourceType::AGENCY;
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requesterPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new RequestCertificationInput(
            $resourceType,
            $wikiIdentifier,
            $ownerAccountIdentifier,
            $requesterPrincipalIdentifier,
        );

        $this->assertSame($resourceType, $input->resourceType());
        $this->assertSame($wikiIdentifier, $input->wikiIdentifier());
        $this->assertSame($ownerAccountIdentifier, $input->ownerAccountIdentifier());
        $this->assertSame($requesterPrincipalIdentifier, $input->requesterPrincipalIdentifier());
    }
}
