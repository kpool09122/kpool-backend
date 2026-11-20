<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Effect;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Principal\Domain\ValueObject\Statement;

class StatementTest extends TestCase
{
    public function testContract(): void
    {
        $statement = new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::PRINCIPAL_GROUP]);
        self::assertSame(Effect::DENY, $statement->effect());
        self::assertSame([Action::PRINCIPAL_GROUP_MANAGE], $statement->actions());
        self::assertSame([ResourceType::PRINCIPAL_GROUP], $statement->resourceTypes());
    }
}
