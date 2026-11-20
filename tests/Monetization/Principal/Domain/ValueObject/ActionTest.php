<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\Action;

class ActionTest extends TestCase
{
    public function testContract(): void
    {
        self::assertSame(['principal_group_read', 'principal_group_manage'], array_map(static fn (Action $action): string => $action->value, Action::cases()));
    }
}
