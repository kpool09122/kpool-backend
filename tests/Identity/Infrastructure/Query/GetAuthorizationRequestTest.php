<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInterface;
use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Identity\Domain\Repository\AuthorizationScopeSettingsRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationScopeSettingsIdentifier;
use Tests\Helper\AuthorizationRequestScenario;
use Tests\TestCase;

#[Group('useDb')]
class GetAuthorizationRequestTest extends TestCase
{
    /** @return array<string, array{list<AuthorizationScope>, list<AuthorizationScope>|null, list<string>}> */
    public static function scopeSettings(): array
    {
        return [
            'not configured' => [AuthorizationScope::cases(), null, []],
            'all' => [AuthorizationScope::cases(), AuthorizationScope::cases(), array_map(static fn (AuthorizationScope $scope): string => $scope->value, AuthorizationScope::cases())],
            'partial' => [[AuthorizationScope::WikiRead, AuthorizationScope::DraftWrite], [AuthorizationScope::DraftWrite, AuthorizationScope::ImageRead], ['wiki-drafts:write']],
            'disjoint' => [[AuthorizationScope::WikiRead], [AuthorizationScope::DraftRead], []],
            'empty setting' => [AuthorizationScope::cases(), [], []],
            'requested order' => [[AuthorizationScope::DraftWrite, AuthorizationScope::WikiRead], [AuthorizationScope::WikiRead, AuthorizationScope::DraftWrite], ['wiki-drafts:write', 'wiki:read']],
        ];
    }

    /**
     * @param list<AuthorizationScope> $requested
     * @param list<AuthorizationScope>|null $allowed
     * @param list<string> $expected
     */
    #[DataProvider('scopeSettings')]
    public function testReturnsOnlyAllowedCandidates(array $requested, ?array $allowed, array $expected): void
    {
        $scenario = AuthorizationRequestScenario::create();
        $parameters = $scenario->pending->parameters;
        $pending = $scenario->authorizationRequestStorageService->store(new AuthorizationCodeParameters($parameters->clientIdentifier, $parameters->redirectUri, $requested, $parameters->resource, $parameters->codeChallenge, $parameters->state), $scenario->base->identityIdentifier());
        if ($allowed !== null) {
            app(AuthorizationScopeSettingsRepositoryInterface::class)->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $scenario->base->identityIdentifier(), $allowed));
        }
        $query = app(GetAuthorizationRequestInterface::class);
        $result = $query->process(new GetAuthorizationRequestInput($pending->identifier, $scenario->base->identityIdentifier()))->toArray();
        $this->assertSame($expected, array_column($result['scopes'], 'scope'));
    }

    public function testReturnsRequestedScopesWithoutSelectingAnAccount(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        $query = app(GetAuthorizationRequestInterface::class);
        $identity = $scenario->base->identityIdentifier();
        app(AuthorizationScopeSettingsRepositoryInterface::class)->save(new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $identity, [AuthorizationScope::WikiRead]));
        $input = new GetAuthorizationRequestInput($scenario->pending->identifier, $identity);
        $sessionBeforeRead = $scenario->request->session()->all();

        $result = $query->process($input)->toArray();

        $this->assertSame($sessionBeforeRead, $scenario->request->session()->all());
        $this->assertNull(app(CurrentAccountServiceInterface::class)->find($identity));
        $this->assertSame('ChatGPT', $result['clientName']);
        $this->assertContains(['scope' => 'wiki:read', 'description' => 'Wiki の閲覧'], $result['scopes']);
        $this->assertArrayNotHasKey('accounts', $result);
        $this->assertArrayNotHasKey('originalAccountIdentifier', $result);
    }
}
