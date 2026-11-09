<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Factory;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Infrastructure\Factory\ArchivedIdentityFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

class ArchivedIdentityFactoryTest extends TestCase
{
    public function testCreatesEntityWithGeneratedIdentifier(): void
    {
        $uuid = '019c9b4c-0000-7000-8000-000000000002';
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects($this->once())->method('generate')->willReturn($uuid);
        $factory = new ArchivedIdentityFactory($uuidGenerator);
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $language = Language::JAPANESE;
        $identityCreatedAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');
        $before = new DateTimeImmutable();
        $region = new SubdivisionCode(CountryCode::GERMANY, 'DE-BE');
        $entity = $factory->create($identityIdentifier, $language, $identityCreatedAt, $region->country(), $region);
        $after = new DateTimeImmutable();

        $this->assertSame($uuid, (string) $entity->archivedIdentityIdentifier());
        $this->assertSame($identityIdentifier, $entity->identityIdentifier());
        self::assertSame($region, $entity->registrationRegion());
        $this->assertSame($language, $entity->language());
        $this->assertSame($identityCreatedAt, $entity->identityCreatedAt());
        $this->assertGreaterThanOrEqual($before, $entity->archivedAt());
        $this->assertLessThanOrEqual($after, $entity->archivedAt());
    }
}
