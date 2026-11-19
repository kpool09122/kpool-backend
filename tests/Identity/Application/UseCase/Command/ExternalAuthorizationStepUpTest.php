<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command;

use Mockery;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorization;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationInput;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationOutput;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorization;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationInput;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationOutput;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettings;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsInput;
use Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings\UpdateAuthorizationScopeSettingsOutput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\GetAuthorizationScopeSettingsInput;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInput;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\Repository\AuthorizationScopeSettingsRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Infrastructure\Query\GetAuthorizationScopeSettings;
use Source\Identity\Infrastructure\Query\ListAuthorizationConnections;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class ExternalAuthorizationStepUpTest extends TestCase
{
    private const string IDENTITY = '0199f700-0000-7000-8000-000000000099';

    protected function setUp(): void
    {
        parent::setUp();
        $stepUpAuthenticationStorageService = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUpAuthenticationStorageService->shouldReceive('requireValid')->once()->with(
            Mockery::on(static fn (IdentityIdentifier $identity): bool => (string) $identity === self::IDENTITY),
            StepUpAuthenticationScope::RECENT_AUTHENTICATION,
        )->andThrow(new StepUpAuthenticationRequiredException());
        $this->app()->instance(StepUpAuthenticationStorageServiceInterface::class, $stepUpAuthenticationStorageService);
        $authorizationConnectionRepository = Mockery::mock(AuthorizationConnectionRepositoryInterface::class);
        $authorizationConnectionRepository->shouldNotReceive('find', 'save');
        $this->app()->instance(AuthorizationConnectionRepositoryInterface::class, $authorizationConnectionRepository);
        $authorizationScopeSettingsRepository = Mockery::mock(AuthorizationScopeSettingsRepositoryInterface::class);
        $authorizationScopeSettingsRepository->shouldNotReceive('find', 'save');
        $this->app()->instance(AuthorizationScopeSettingsRepositoryInterface::class, $authorizationScopeSettingsRepository);
        $this->expectException(StepUpAuthenticationRequiredException::class);
    }

    public function testApprovalRequiresRecentAuthenticationBeforeIssuingACode(): void
    {
        app(ApproveAuthorization::class)->process(
            new ApproveAuthorizationInput('request', new IdentityIdentifier(self::IDENTITY), [AuthorizationScope::WikiRead]),
            new ApproveAuthorizationOutput(),
        );
    }

    public function testRevokeRequiresRecentAuthenticationBeforeLoadingOrMutatingConnection(): void
    {
        app(RevokeAuthorization::class)->process(
            new RevokeAuthorizationInput(new IdentityIdentifier(self::IDENTITY), new AuthorizationConnectionIdentifier('0199f700-0000-7000-8000-000000000098')),
            new RevokeAuthorizationOutput(),
        );
    }

    public function testSavingEvenEmptyScopesRequiresRecentAuthentication(): void
    {
        app(UpdateAuthorizationScopeSettings::class)->process(
            new UpdateAuthorizationScopeSettingsInput(new IdentityIdentifier(self::IDENTITY), []),
            new UpdateAuthorizationScopeSettingsOutput(),
        );
    }

    public function testConnectionListRequiresRecentAuthenticationBeforeQueryingDatabase(): void
    {
        app(ListAuthorizationConnections::class)->process(new ListAuthorizationConnectionsInput(new IdentityIdentifier(self::IDENTITY)));
    }

    public function testScopeSettingsRequireRecentAuthenticationBeforeQueryingDatabase(): void
    {
        app(GetAuthorizationScopeSettings::class)->process(new GetAuthorizationScopeSettingsInput(new IdentityIdentifier(self::IDENTITY)));
    }
}
