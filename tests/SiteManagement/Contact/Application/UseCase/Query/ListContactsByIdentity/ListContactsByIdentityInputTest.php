<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInput;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ListContactsByIdentityInputTest extends TestCase
{
    public function testRequesterAndTargetIdentifiersArePreservedSeparately(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsByIdentityInput($principalIdentifier, $targetIdentityIdentifier);

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($targetIdentityIdentifier, $input->targetIdentityIdentifier());
    }
}
