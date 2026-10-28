<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest\SendVideoWatchRequestInput;
use Tests\Helper\StrTestHelper;

class SendVideoWatchRequestInputTest extends TestCase
{
    public function testPreservesExactValueObjects(): void
    {
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new SendVideoWatchRequestInput($video, $principal);
        self::assertSame($video, $input->videoLinkIdentifier());
        self::assertSame($principal, $input->principalIdentifier());
    }
}
