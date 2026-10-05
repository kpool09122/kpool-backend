<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Condition;
use Source\SiteManagement\Principal\Domain\ValueObject\Effect;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Principal\Domain\ValueObject\Statement;

class StatementTest extends TestCase
{
    public function testStatementPreservesAuthorizationConstraints(): void
    {
        $statement = new Statement(Effect::DENY, [Action::CONTACT_VIEW], [ResourceType::CONTACT], Condition::OWN_CONTACT);
        self::assertSame(Effect::DENY, $statement->effect());
        self::assertSame([Action::CONTACT_VIEW], $statement->actions());
        self::assertSame([ResourceType::CONTACT], $statement->resourceTypes());
        self::assertSame(Condition::OWN_CONTACT, $statement->condition());
    }
}
