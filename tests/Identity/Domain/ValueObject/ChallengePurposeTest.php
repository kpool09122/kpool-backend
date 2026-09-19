<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\ChallengePurpose;

class ChallengePurposeTest extends TestCase
{
    public function testItDefinesEverySupportedChallengePurpose(): void
    {
        $this->assertSame(
            ['registration', 'authentication', 'addition'],
            array_map(
                static fn (ChallengePurpose $purpose): string => $purpose->value,
                ChallengePurpose::cases(),
            ),
        );
    }
}
