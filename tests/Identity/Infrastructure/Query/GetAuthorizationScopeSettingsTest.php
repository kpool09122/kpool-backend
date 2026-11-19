<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsInput;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsInterface;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsOutput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\GetAuthorizationScopeSettingsInput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\GetAuthorizationScopeSettingsInterface;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\Repository\AuthorizationScopeSettingsRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAuthorizationConnection;
use Tests\TestCase;

#[Group('useDb')]
class GetAuthorizationScopeSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $stepUpAuthenticationStorageService = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUpAuthenticationStorageService->shouldReceive('requireValid')->with(Mockery::type(IdentityIdentifier::class), StepUpAuthenticationScope::RECENT_AUTHENTICATION)
            ->andReturnUsing(static fn (IdentityIdentifier $identity): StepUpAuthentication => new StepUpAuthentication($identity, StepUpAuthenticationMethod::PASSKEY, new DateTimeImmutable(), StepUpAuthenticationScope::RECENT_AUTHENTICATION, new DateTimeImmutable('+10 minutes')));
        $this->app()->instance(StepUpAuthenticationStorageServiceInterface::class, $stepUpAuthenticationStorageService);
    }

    public function testDefaultSaveEmptyAndIdentityIsolation(): void
    {
        $first = CreateAuthorizationConnection::create();
        $second = CreateAuthorizationConnection::create();
        $query = app(GetAuthorizationScopeSettingsInterface::class);
        $input = new GetAuthorizationScopeSettingsInput($first->identityIdentifier());
        $this->assertSame(['scopes' => []], $query->process($input)->toArray());
        $repository = app(AuthorizationScopeSettingsRepositoryInterface::class);

        $command = app(UpdateAuthorizationScopeSettingsInterface::class);
        $output = new UpdateAuthorizationScopeSettingsOutput();
        $command->process(new UpdateAuthorizationScopeSettingsInput($first->identityIdentifier(), [AuthorizationScope::WikiRead]), $output);
        $this->assertSame([], $output->toArray());
        $this->assertSame(['scopes' => ['wiki:read']], $query->process($input)->toArray());
        $savedSettings = $repository->find($first->identityIdentifier());
        $this->assertNotNull($savedSettings);
        $settingsIdentifier = (string) $savedSettings->identifier();
        $command->process(new UpdateAuthorizationScopeSettingsInput($first->identityIdentifier(), []), $output);
        $this->assertSame(['scopes' => []], $query->process($input)->toArray());
        $updatedSettings = $repository->find($first->identityIdentifier());
        $this->assertNotNull($updatedSettings);
        $this->assertSame([], $updatedSettings->scopes());
        $this->assertSame($settingsIdentifier, (string) $updatedSettings->identifier());
        $this->assertSame(['scopes' => []], $query->process(new GetAuthorizationScopeSettingsInput($second->identityIdentifier()))->toArray());
        $this->assertSame($first->scopes(), app(AuthorizationConnectionRepositoryInterface::class)->find($first->identifier())?->scopes());
    }
}
