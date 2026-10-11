<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator\GrantWikiOperatorOutput;

class GrantWikiOperatorOutputTest extends TestCase
{
    public function testSerializesToAnEmptyArray(): void
    {
        $this->assertSame([], (new GrantWikiOperatorOutput())->toArray());
    }
}
