<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContactsByIdentity\ListContactsByIdentityInput;
use Tests\Helper\StrTestHelper;

class ListContactsByIdentityInputTest extends TestCase
{
    public function testRequesterAndTargetIdentifiersArePreservedSeparately(): void
    {
        $requesterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $targetIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsByIdentityInput($requesterIdentityIdentifier, $targetIdentityIdentifier);

        $this->assertSame($requesterIdentityIdentifier, $input->requesterIdentityIdentifier());
        $this->assertSame($targetIdentityIdentifier, $input->targetIdentityIdentifier());
    }
}
