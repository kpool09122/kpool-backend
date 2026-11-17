<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Models\Account\Principal;
use Application\Models\Identity\AuthorizationConnection;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConnectionServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Application\Service\PendingAuthorization;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorization;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationInput;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationOutput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInput;
use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Identity\Domain\Factory\AuthorizationConnectionFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\Repository\AuthorizationScopeSettingsRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationScopeSettingsIdentifier;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Query\GetAuthorizationRequest;
use Tests\Helper\AuthorizationRequestScenario;
use Tests\TestCase;

#[Group('useDb')]
class AuthorizationConnectionFlowTest extends TestCase
{
    public function testPersistsOnlySelectedScopesAndIssuesRealCodeFromStoredParameters(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        app(AuthorizationScopeSettingsRepositoryInterface::class)->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $scenario->base->identityIdentifier(), $scenario->pending->parameters->requestedScopes));
        $output = new ApproveAuthorizationOutput();
        $input = $this->input($scenario);
        $useCase = app(ApproveAuthorization::class);
        DB::transaction(fn () => $useCase->process($input, $output));
        $model = AuthorizationConnection::query()->sole();
        $this->assertSame(['wiki-drafts:write'], $model->scopes);
        $this->assertSame((string) $scenario->base->originalAccountIdentifier(), $model->original_account_id);
        $this->assertSame((string) $scenario->base->originalPrincipalIdentifier(), $model->original_principal_id);
        $this->assertSame((string) $scenario->base->identityIdentifier(), $model->identity_id);
        $this->assertSame((string) $scenario->base->clientIdentifier(), $model->client_id);
        $url = $output->toArray()['redirectUri'];
        $this->assertStringStartsWith($scenario->pending->parameters->redirectUri . '?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(' original state & value ', $query['state']);
        $this->assertIsString($query['code']);
        $this->assertArrayNotHasKey('access_token', $query);
        $this->assertSame(1, DB::table('oauth_auth_codes')->where('authorization_connection_id', $model->id)->count());
    }

    public function testCodeFailureRollsBackEarlierConnectionWriteAndAllowsRetry(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        app(AuthorizationScopeSettingsRepositoryInterface::class)->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $scenario->base->identityIdentifier(), $scenario->pending->parameters->requestedScopes));
        /** @var AuthorizationConnectionServiceInterface&MockInterface $codes */
        $codes = Mockery::mock(AuthorizationConnectionServiceInterface::class);
        $codes->shouldReceive('issueCode')->once()->andReturnUsing(function (AuthorizationConnectionIdentifier $id, AuthorizationCodeParameters $parameters): never {
            $this->assertTrue(AuthorizationConnection::query()->whereKey((string) $id)->exists());
            $this->assertSame(' original state & value ', $parameters->state);

            throw new InvalidAuthorizationRequestException();
        });
        $useCase = new ApproveAuthorization(
            $scenario->authorizationRequestStorageService,
            app(AuthorizationRequestValidatorInterface::class),
            app(AuthorizationAccountServiceInterface::class),
            app(AuthorizationConnectionFactoryInterface::class),
            app(AuthorizationConnectionRepositoryInterface::class),
            $codes,
            app(AuthorizationScopeSettingsRepositoryInterface::class),
        );

        try {
            DB::transaction(fn () => $useCase->process($this->input($scenario), new ApproveAuthorizationOutput()));
            $this->fail('Expected code failure.');
        } catch (InvalidAuthorizationRequestException) {
            $this->assertSame(0, AuthorizationConnection::query()->count());
            $this->assertSame(0, DB::table('oauth_auth_codes')->count());
        }
        $this->assertSame($scenario->pending->identifier, $scenario->authorizationRequestStorageService->requireValid($scenario->pending->identifier, $scenario->base->identityIdentifier())->identifier);
        $output = new ApproveAuthorizationOutput();
        $useCase = app(ApproveAuthorization::class);
        DB::transaction(fn () => $useCase->process($this->input($scenario), $output));
        $this->assertSame(1, AuthorizationConnection::query()->count());
        $this->assertStringContainsString('code=', $output->toArray()['redirectUri']);
    }

    /** @return array<string, array{string}> */
    public static function invalidScopes(): array
    {
        return ['scope expansion' => ['scope'], 'empty scopes' => ['emptyScopes'], 'duplicate scopes' => ['duplicateScopes']];
    }

    #[DataProvider('invalidScopes')]
    public function testRejectsInvalidScopesWithoutConnection(string $case): void
    {
        $scenario = AuthorizationRequestScenario::create();
        app(AuthorizationScopeSettingsRepositoryInterface::class)->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $scenario->base->identityIdentifier(), $scenario->pending->parameters->requestedScopes));
        $scopes = match($case) {
            'emptyScopes' => [], 'duplicateScopes' => [AuthorizationScope::WikiRead, AuthorizationScope::WikiRead], default => [AuthorizationScope::WikiRead]
        };
        if ($case === 'scope') {
            $session = $scenario->request->session();
            $pending = $scenario->pending;
            $parameters = new AuthorizationCodeParameters($pending->parameters->clientIdentifier, $pending->parameters->redirectUri, [AuthorizationScope::DraftRead], $pending->parameters->resource, $pending->parameters->codeChallenge, $pending->parameters->state);
            $session->put('oauth_authorization_requests', [$pending->identifier => new PendingAuthorization($pending->identifier, $parameters, $pending->expiresAt, $scenario->base->identityIdentifier())]);
        }
        $input = new ApproveAuthorizationInput($scenario->pending->identifier, $scenario->base->identityIdentifier(), $scopes);

        try {
            $useCase = app(ApproveAuthorization::class);
            $useCase->process($input, new ApproveAuthorizationOutput());
            $this->fail('Invalid authorization request was accepted.');
        } catch (InvalidAuthorizationRequestException) {
            $this->assertSame(0, AuthorizationConnection::query()->count());
            $this->assertSame(0, DB::table('oauth_auth_codes')->count());
        }
    }

    public function testRechecksOriginalMembershipAfterScreenWasRead(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        app(AuthorizationScopeSettingsRepositoryInterface::class)->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $scenario->base->identityIdentifier(), $scenario->pending->parameters->requestedScopes));
        $query = app(GetAuthorizationRequest::class);
        $query->process(new GetAuthorizationRequestInput($scenario->pending->identifier, $scenario->base->identityIdentifier()));
        Principal::query()->whereKey((string) $scenario->base->originalPrincipalIdentifier())->update(['status' => 'suspended']);
        $this->expectException(AuthorizationAccountUnavailableException::class);
        $useCase = app(ApproveAuthorization::class);
        $useCase->process($this->input($scenario), new ApproveAuthorizationOutput());
    }

    /** @return array<string, array{list<AuthorizationScope>}> */
    public static function disallowedSettings(): array
    {
        return ['shrunk after display' => [[AuthorizationScope::WikiRead]], 'all disabled' => [[]]];
    }

    /** @param list<AuthorizationScope> $allowed */
    #[DataProvider('disallowedSettings')]
    public function testRechecksLatestAllowedScopesAfterScreenWasRead(array $allowed): void
    {
        $scenario = AuthorizationRequestScenario::create();
        app(AuthorizationScopeSettingsRepositoryInterface::class)->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $scenario->base->identityIdentifier(), $scenario->pending->parameters->requestedScopes));
        $identity = $scenario->base->identityIdentifier();
        app(GetAuthorizationRequest::class)->process(new GetAuthorizationRequestInput($scenario->pending->identifier, $identity));
        app(AuthorizationScopeSettingsRepositoryInterface::class)->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $identity, $allowed));

        try {
            app(ApproveAuthorization::class)->process($this->input($scenario), new ApproveAuthorizationOutput());
            $this->fail('Disallowed scope accepted.');
        } catch (InvalidAuthorizationRequestException) {
            $this->assertSame(0, AuthorizationConnection::query()->count());
            $this->assertSame(0, DB::table('oauth_auth_codes')->count());
            $this->assertSame($scenario->pending->identifier, $scenario->authorizationRequestStorageService->requireValid($scenario->pending->identifier, $identity)->identifier);
        }
    }

    public function testOneSettingRestrictsMultipleClientsWithoutChangingExistingConsents(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        $identity = $scenario->base->identityIdentifier();
        $settings = app(AuthorizationScopeSettingsRepositoryInterface::class);
        $settings->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $identity, [AuthorizationScope::DraftWrite]));
        $parameters = $scenario->pending->parameters;
        $secondClient = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Second client', [$parameters->redirectUri], false);
        foreach ([$parameters->clientIdentifier, new OAuthClientIdentifier($secondClient->id)] as $client) {
            $pending = $scenario->authorizationRequestStorageService->store(new AuthorizationCodeParameters($client, $parameters->redirectUri, $parameters->requestedScopes, $parameters->resource, $parameters->codeChallenge, $parameters->state), $identity);
            $view = app(GetAuthorizationRequest::class)->process(new GetAuthorizationRequestInput($pending->identifier, $identity))->toArray();
            $this->assertSame(['wiki-drafts:write'], array_column($view['scopes'], 'scope'));
            $output = new ApproveAuthorizationOutput();
            $useCase = app(ApproveAuthorization::class);
            DB::transaction(fn () => $useCase->process(new ApproveAuthorizationInput($pending->identifier, $identity, [AuthorizationScope::DraftWrite]), $output));
            $this->assertStringContainsString('code=', $output->toArray()['redirectUri']);
        }
        $before = AuthorizationConnection::query()->orderBy('id')->get()->toArray();
        $settings->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $identity, []));
        $this->assertSame($before, AuthorizationConnection::query()->orderBy('id')->get()->toArray());
        $this->assertSame(2, AuthorizationConnection::query()->count());
        $this->assertSame(2, DB::table('oauth_auth_codes')->count());
        foreach (AuthorizationConnection::query()->get() as $connection) {
            $this->assertSame(['wiki-drafts:write'], $connection->scopes);
        }
    }

    private function input(AuthorizationRequestScenario $scenario): ApproveAuthorizationInput
    {
        return new ApproveAuthorizationInput($scenario->pending->identifier, $scenario->base->identityIdentifier(), [AuthorizationScope::DraftWrite]);
    }
}
