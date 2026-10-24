<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\LikeVideoLink;

use Source\Wiki\VideoLink\Application\UseCase\Command\LikeVideoLink\LikeVideoLinkOutput;
use Tests\TestCase;

class LikeVideoLinkOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $output = new LikeVideoLinkOutput();

        $this->assertSame([], $output->toArray());
    }
}
