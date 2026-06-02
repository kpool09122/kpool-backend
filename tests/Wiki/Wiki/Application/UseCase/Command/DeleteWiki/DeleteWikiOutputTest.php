<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\DeleteWiki;

use Source\Wiki\Wiki\Application\UseCase\Command\DeleteWiki\DeleteWikiOutput;
use Tests\TestCase;

class DeleteWikiOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $output = new DeleteWikiOutput();

        $this->assertSame([], $output->toArray());
    }
}
