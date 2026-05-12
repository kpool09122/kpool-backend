<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\ValueObject;

use InvalidArgumentException;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Tests\TestCase;

class RightsConfirmationAgreedTest extends TestCase
{
    public function test__construct(): void
    {
        $value = new RightsConfirmationAgreed(true);

        $this->assertTrue($value->value());
    }

    public function test__constructThrowsExceptionWhenNotAgreed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RightsConfirmationAgreed(false);
    }
}
