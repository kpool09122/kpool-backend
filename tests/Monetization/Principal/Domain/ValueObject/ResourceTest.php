<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class ResourceTest extends TestCase
{
    public function testContract(): void
    {
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $resource = new Resource(ResourceType::PRINCIPAL_GROUP, $account);
        self::assertSame(ResourceType::PRINCIPAL_GROUP, $resource->type());
        self::assertSame($account, $resource->accountIdentifier());
    }
}
