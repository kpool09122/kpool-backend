<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests\ListMyVideoWatchRequestsInput;
use Tests\Helper\StrTestHelper;

class ListMyVideoWatchRequestsInputTest extends TestCase
{
    public function testPreservesExactValueObjects(): void
    {
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ListMyVideoWatchRequestsInput([$video], $principal);
        self::assertSame([$video], $input->videoLinkIdentifiers());
        self::assertSame($principal, $input->principalIdentifier());
    }

    public function testRejectsEmptyIds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListMyVideoWatchRequestsInput([], new PrincipalIdentifier(StrTestHelper::generateUuid()));
    }

    public function testRejectsMoreThanOneHundredIds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListMyVideoWatchRequestsInput(array_fill(0, 101, new VideoLinkIdentifier(StrTestHelper::generateUuid())), new PrincipalIdentifier(StrTestHelper::generateUuid()));
    }
}
