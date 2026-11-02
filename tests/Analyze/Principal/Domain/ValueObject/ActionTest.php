<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Domain\ValueObject\Action;

class ActionTest extends TestCase
{
    public function testPersistedVocabulary(): void
    {
        self::assertSame(['analysis:view'], array_column(Action::cases(), 'value'));
    }
}
