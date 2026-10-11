<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorOutput;

class RevokeWikiOperatorOutputTest extends TestCase
{
    public function testSerializesToAnEmptyArray(): void
    {
        $this->assertSame([], (new RevokeWikiOperatorOutput())->toArray());
    }
}
