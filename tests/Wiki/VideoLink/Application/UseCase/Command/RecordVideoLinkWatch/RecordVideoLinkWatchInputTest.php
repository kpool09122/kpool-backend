<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\RecordVideoLinkWatch;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Command\RecordVideoLinkWatch\RecordVideoLinkWatchInput;
use Tests\Helper\StrTestHelper;

class RecordVideoLinkWatchInputTest extends TestCase
{
    public function testPreservesExactValueObjects(): void
    {
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new RecordVideoLinkWatchInput($video, $principal);
        self::assertSame($video, $input->videoLinkIdentifier());
        self::assertSame($principal, $input->principalIdentifier());
    }
}
