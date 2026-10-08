<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;

class ResourceTest extends TestCase
{
    public function testOwnerIsOptionalAndPreserved(): void
    {
        $owner = new PrincipalIdentifier('69200000-0000-7000-8000-000000000099');
        self::assertNull((new Resource(ResourceType::CONTACT))->ownerPrincipalIdentifier());
        $resource = new Resource(ResourceType::CONTACT, $owner);
        self::assertSame($owner, $resource->ownerPrincipalIdentifier());
        self::assertSame(ResourceType::CONTACT, $resource->type());
    }
}
