<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RevokeAuthorization;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationOutput;

class RevokeAuthorizationOutputTest extends TestCase
{
    public function testSerializesToEmptyArray(): void
    {
        self::assertSame([], (new RevokeAuthorizationOutput())->toArray());
    }
}
