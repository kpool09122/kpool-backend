<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Factory;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Domain\Factory\AuthorizationScopeSettingsFactoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Infrastructure\Factory\AuthorizationScopeSettingsFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class AuthorizationScopeSettingsFactoryTest extends TestCase
{
    /** @return array<string, array{list<AuthorizationScope>}> */
    public static function scopes(): array
    {
        return ['deny all' => [[]], 'explicit allowance' => [[AuthorizationScope::WikiRead, AuthorizationScope::DraftWrite]]];
    }

    /** @param list<AuthorizationScope> $scopes */
    #[DataProvider('scopes')]
    public function testCreatesSettingsForTheSpecifiedIdentity(array $scopes): void
    {
        $identityIdentifier = new IdentityIdentifier('0199f700-0000-7000-8000-000000000099');
        $uuid = '0199f700-0000-7000-8000-000000000098';
        $uuidGenerator = Mockery::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->once()->andReturn($uuid);
        $this->app()->instance(UuidGeneratorInterface::class, $uuidGenerator);
        $factory = app(AuthorizationScopeSettingsFactoryInterface::class);
        $this->assertInstanceOf(AuthorizationScopeSettingsFactory::class, $factory);

        $settings = $factory->create($identityIdentifier, $scopes);

        $this->assertSame($uuid, (string) $settings->identifier());
        $this->assertSame($identityIdentifier, $settings->identityIdentifier());
        $this->assertSame($scopes, $settings->scopes());
    }
}
