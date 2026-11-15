<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Models\Account\Delegation;
use Application\Models\Account\Principal;
use Application\Models\Identity\AuthorizationConsent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConsentServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Application\Service\PendingAuthorization;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorization as ApproveConsent;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationInput;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationOutput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInput;
use Source\Identity\Domain\Factory\AuthorizationConsentFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Identity\Infrastructure\Query\GetAuthorizationRequest;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\AuthorizationRequestScenario;
use Tests\Helper\CreateAccount;
use Tests\TestCase;

#[Group('useDb')]
class AuthorizationConsentFlowTest extends TestCase
{
    public function testDelegatedOnlyConsentNeverAddsOriginalAccountToGrantedTargets(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        $target = new AccountIdentifier((string) Str::uuid7());
        $delegation = new DelegationIdentifier((string) Str::uuid7());
        CreateAccount::create((string) $target);
        Delegation::query()->create([
            'id' => (string) $delegation,
            'affiliation_id' => (string) Str::uuid7(),
            'delegate_account_id' => (string) $scenario->base->originalAccountIdentifier(),
            'delegator_account_id' => (string) $target,
            'requested_by_account_id' => (string) $target,
            'status' => 'approved',
            'direction' => DelegationDirection::FROM_AGENCY->value,
            'requested_at' => now(),
        ]);
        $policyEvaluator = $this->createStub(PolicyEvaluatorInterface::class);
        $policyEvaluator->method('evaluate')->willReturn(true);
        app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $useCase = app(ApproveConsent::class);
        $input = new ApproveAuthorizationInput($scenario->pending->identifier, $scenario->base->identityIdentifier(), [AuthorizationScope::WikiRead], [new AuthorizationTarget($target, $delegation)], $target);
        DB::transaction(fn () => $useCase->process($input, new ApproveAuthorizationOutput()));
        $model = AuthorizationConsent::query()->sole();
        $this->assertSame([['account_id' => (string) $target, 'delegation_id' => (string) $delegation]], $model->targets);
        $this->assertSame((string) $scenario->base->originalAccountIdentifier(), $model->original_account_id);
        $this->assertSame((string) $scenario->base->originalPrincipalIdentifier(), $model->original_principal_id);
        $this->assertSame((string) $target, $model->initial_account_id);
    }

    public function testPersistsOnlySelectedScopesAndIssuesRealCodeFromStoredParameters(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        $output = new ApproveAuthorizationOutput();
        $input = $this->input($scenario);
        $useCase = app(ApproveConsent::class);
        DB::transaction(fn () => $useCase->process($input, $output));
        $model = AuthorizationConsent::query()->sole();
        $this->assertSame(['wiki-drafts:write'], $model->scopes);
        $this->assertSame([['account_id' => (string) $scenario->base->originalAccountIdentifier(), 'delegation_id' => null]], $model->targets);
        $this->assertSame((string) $scenario->base->identityIdentifier(), $model->identity_id);
        $this->assertSame((string) $scenario->base->clientIdentifier(), $model->client_id);
        $url = $output->toArray()['redirectUri'];
        $this->assertStringStartsWith($scenario->pending->parameters->redirectUri . '?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(' original state & value ', $query['state']);
        $this->assertIsString($query['code']);
        $this->assertArrayNotHasKey('access_token', $query);
        $this->assertSame(1, DB::table('oauth_auth_codes')->where('authorization_consent_id', $model->id)->count());
    }

    public function testCodeFailureRollsBackEarlierConsentWriteAndAllowsRetry(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        /** @var AuthorizationConsentServiceInterface&MockInterface $codes */
        $codes = Mockery::mock(AuthorizationConsentServiceInterface::class);
        $codes->shouldReceive('issueCode')->once()->andReturnUsing(function (AuthorizationConsentIdentifier $id, AuthorizationCodeParameters $parameters): never {
            $this->assertTrue(AuthorizationConsent::query()->whereKey((string) $id)->exists());
            $this->assertSame(' original state & value ', $parameters->state);

            throw new InvalidAuthorizationConsentException();
        });
        $useCase = new ApproveConsent(
            $scenario->authorizationRequestStorageService,
            app(AuthorizationRequestValidatorInterface::class),
            app(AuthorizationAccountServiceInterface::class),
            app(AuthorizationConsentFactoryInterface::class),
            app(AuthorizationConsentRepositoryInterface::class),
            $codes,
        );

        try {
            DB::transaction(fn () => $useCase->process($this->input($scenario), new ApproveAuthorizationOutput()));
            $this->fail('Expected code failure.');
        } catch (InvalidAuthorizationConsentException) {
            $this->assertSame(0, AuthorizationConsent::query()->count());
            $this->assertSame(0, DB::table('oauth_auth_codes')->count());
        }
        $this->assertSame($scenario->pending->identifier, $scenario->authorizationRequestStorageService->requireValid($scenario->pending->identifier, $scenario->base->identityIdentifier())->identifier);
        $output = new ApproveAuthorizationOutput();
        $useCase = app(ApproveConsent::class);
        DB::transaction(fn () => $useCase->process($this->input($scenario), $output));
        $this->assertSame(1, AuthorizationConsent::query()->count());
        $this->assertStringContainsString('code=', $output->toArray()['redirectUri']);
    }

    /** @return array<string, array{string}> */
    public static function invalidSelections(): array
    {
        return ['scope expansion' => ['scope'], 'empty scopes' => ['emptyScopes'], 'duplicate scopes' => ['duplicateScopes'], 'foreign account' => ['foreign'], 'forged delegation' => ['delegation'], 'duplicate accounts' => ['duplicateAccounts'], 'unselected initial' => ['initial'], 'empty targets' => ['emptyTargets']];
    }

    #[DataProvider('invalidSelections')]
    public function testRejectsTamperedSelectionsWithoutConsent(string $case): void
    {
        $scenario = AuthorizationRequestScenario::create();
        $original = $scenario->base->originalAccountIdentifier();
        $foreign = new AccountIdentifier((string) Str::uuid7());
        $scopes = match($case) {
            'emptyScopes' => [], 'duplicateScopes' => [AuthorizationScope::WikiRead, AuthorizationScope::WikiRead], default => [AuthorizationScope::WikiRead]
        };
        if ($case === 'scope') {
            $session = $scenario->request->session();
            $pending = $scenario->pending;
            $parameters = new AuthorizationCodeParameters($pending->parameters->clientIdentifier, $pending->parameters->redirectUri, [AuthorizationScope::DraftRead], $pending->parameters->resource, $pending->parameters->codeChallenge, $pending->parameters->state);
            $session->put('oauth_authorization_requests', [$pending->identifier => new PendingAuthorization($pending->identifier, $parameters, $pending->expiresAt, $scenario->base->identityIdentifier())]);
        }
        $targets = match($case) {
            'foreign' => [new AuthorizationTarget($foreign)], 'delegation' => [new AuthorizationTarget($original, new DelegationIdentifier((string) Str::uuid7()))], 'duplicateAccounts' => [new AuthorizationTarget($original), new AuthorizationTarget($original)], 'emptyTargets' => [], default => [new AuthorizationTarget($original)]
        };
        $input = new ApproveAuthorizationInput($scenario->pending->identifier, $scenario->base->identityIdentifier(), $scopes, $targets, $case === 'initial' ? $foreign : $original);

        try {
            $useCase = app(ApproveConsent::class);
            $useCase->process($input, new ApproveAuthorizationOutput());
            $this->fail('Invalid consent was accepted.');
        } catch (InvalidAuthorizationConsentException) {
            $this->assertSame(0, AuthorizationConsent::query()->count());
            $this->assertSame(0, DB::table('oauth_auth_codes')->count());
        }
    }

    public function testRechecksOriginalMembershipAfterScreenWasRead(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        $query = app(GetAuthorizationRequest::class);
        $query->process(new GetAuthorizationRequestInput($scenario->pending->identifier, $scenario->base->identityIdentifier()));
        Principal::query()->whereKey((string) $scenario->base->originalPrincipalIdentifier())->update(['status' => 'suspended']);
        $this->expectException(AuthorizationAccountUnavailableException::class);
        $useCase = app(ApproveConsent::class);
        $useCase->process($this->input($scenario), new ApproveAuthorizationOutput());
    }

    private function input(AuthorizationRequestScenario $scenario): ApproveAuthorizationInput
    {
        return new ApproveAuthorizationInput($scenario->pending->identifier, $scenario->base->identityIdentifier(), [AuthorizationScope::DraftWrite], [new AuthorizationTarget($scenario->base->originalAccountIdentifier())], $scenario->base->originalAccountIdentifier());
    }
}
