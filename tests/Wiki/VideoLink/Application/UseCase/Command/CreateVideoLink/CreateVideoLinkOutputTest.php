<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkOutput;

class CreateVideoLinkOutputTest extends TestCase
{
    public function testCapturesOnlyNewlyStoredImageForCompensation(): void
    {
        $output = new CreateVideoLinkOutput();
        self::assertNull($output->thumbnailPath());
        $path = new ImagePath('images/cover.webp');
        $output->output($path);
        self::assertSame($path, $output->thumbnailPath());
    }
}
