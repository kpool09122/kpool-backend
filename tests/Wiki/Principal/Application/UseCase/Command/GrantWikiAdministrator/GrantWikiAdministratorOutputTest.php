<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator\GrantWikiAdministratorOutput;

class GrantWikiAdministratorOutputTest extends TestCase
{
    public function testSerializesToAnEmptyArray(): void
    {
        $this->assertSame([], (new GrantWikiAdministratorOutput())->toArray());
    }
}
