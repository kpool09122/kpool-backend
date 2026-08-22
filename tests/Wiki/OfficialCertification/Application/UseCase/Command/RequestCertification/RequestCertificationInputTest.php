<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInput;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;

class RequestCertificationInputTest extends TestCase
{
    public function test__construct(): void
    {
        $resourceType = ResourceType::AGENCY;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requesterPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new RequestCertificationInput(
            $resourceType,
            $translationSetIdentifier,
            $ownerAccountIdentifier,
            $requesterPrincipalIdentifier,
        );

        $this->assertSame($resourceType, $input->resourceType());
        $this->assertSame($translationSetIdentifier, $input->translationSetIdentifier());
        $this->assertSame($ownerAccountIdentifier, $input->ownerAccountIdentifier());
        $this->assertSame($requesterPrincipalIdentifier, $input->requesterPrincipalIdentifier());
    }
}
