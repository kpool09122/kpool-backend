<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink\DeleteVideoLinkInput;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\StrTestHelper;

class DeleteVideoLinkInputTest extends TestCase
{
    public function testPreservesExactIdentifiers(): void
    {
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new DeleteVideoLinkInput($video, $principal);
        self::assertSame($video, $input->videoLinkIdentifier());
        self::assertSame($principal, $input->principalIdentifier());
    }
}
