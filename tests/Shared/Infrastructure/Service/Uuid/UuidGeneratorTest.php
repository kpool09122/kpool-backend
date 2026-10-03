<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service\Uuid;

use PHPUnit\Framework\TestCase;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;

class UuidGeneratorTest extends TestCase
{
    public function testGeneratesIndependentUuidV7Values(): void
    {
        $uuidGenerator = new UuidGenerator();
        $first = $uuidGenerator->generate();
        $second = $uuidGenerator->generate();
        $this->assertTrue(UuidValidator::isValid($first));
        $this->assertTrue(UuidValidator::isValid($second));
        $this->assertNotSame($first, $second);
    }
}
