<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator\RevokeWikiAdministratorOutput;

class RevokeWikiAdministratorOutputTest extends TestCase
{
    public function testSerializesToAnEmptyArray(): void
    {
        $this->assertSame([], (new RevokeWikiAdministratorOutput())->toArray());
    }
}
