<?php

declare(strict_types=1);

namespace Tests\Mcp;

use Application\Http\Context\AccountResolver;
use Application\Mcp\McpToolVisibility;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreateAccountContext;
use Tests\TestCase;

class McpToolVisibilityTest extends TestCase
{
    /** @return list<array{bool, bool, bool, bool}> */
    public static function permissions(): array
    {
        return [[true, true, true, false], [true, false, true, false], [false, true, true, false], [false, false, true, false], [false, false, false, false], [false, false, false, true]];
    }

    #[DataProvider('permissions')]
    public function testDiscoveryUsesSelectedAccountAndEachImagePermission(bool $list, bool $edit, bool $exists, bool $unavailable): void
    {
        $token = $this->verifiedToken();
        $identity = $token->connection->identityIdentifier();
        $selectedAccount = new AccountIdentifier((string) Str::uuid7());
        $principal = $exists ? new Principal(new PrincipalIdentifier((string) Str::uuid7()), $identity, $selectedAccount) : null;
        $resolver = $this->createMock(AccountResolver::class);
        $resolver->expects(self::once())->method('resolve')->with($identity)->willReturnCallback(function () use ($token, $identity, $selectedAccount, $unavailable) {
            $context = request()->attributes->get(ApiAuthenticationContext::class);
            self::assertInstanceOf(ApiAuthenticationContext::class, $context);
            self::assertSame($token->connection->identifier(), $context->connectionIdentifier());
            if ($unavailable) {
                throw new AccountNotFoundException();
            }

            return CreateAccountContext::create($identity, $selectedAccount);
        });
        /** @var PrincipalRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        if (! $unavailable) {
            $repository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->with($identity, $selectedAccount)->andReturn($principal);
        }
        /** @var PolicyEvaluatorInterface&MockInterface $evaluator */
        $evaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        if ($exists) {
            foreach ([[Action::LIST_MANAGED_IMAGES, $list], [Action::UPDATE_IMAGE_METADATA, $edit]] as [$action, $allowed]) {
                $evaluator->shouldReceive('evaluate')->once()->with($principal, $action, Mockery::on(static fn (Resource $resource): bool => $resource->type() === ResourceType::IMAGE))->andReturn($allowed);
            }
        } else {
            $evaluator->shouldNotReceive('evaluate');
        }
        $visibility = new McpToolVisibility($resolver, $repository, $evaluator);
        $definitions = [['name' => 'get_account_context'], ['name' => 'list_managed_wiki_images'], ['name' => 'update_wiki_image_metadata']];
        $expected = [$definitions[0]];
        if ($list) {
            $expected[] = $definitions[1];
        }
        if ($edit) {
            $expected[] = $definitions[2];
        }
        self::assertSame($expected, $visibility->filter($definitions, $token));
        self::assertFalse(request()->attributes->has(ApiAuthenticationContext::class));
    }

    /** @param list<AuthorizationScope> $scopes */
    private function verifiedToken(array $scopes = [AuthorizationScope::WikiRead]): ValidatedAuthorizationToken
    {
        $uuid = (string) Str::uuid7();
        $connection = new AuthorizationConnection(new AuthorizationConnectionIdentifier($uuid), new IdentityIdentifier($uuid), new OAuthClientIdentifier($uuid), AuthorizationScope::cases(), new AccountIdentifier($uuid), new AccountPrincipalIdentifier($uuid), 'https://auth.example.test/mcp', new DateTimeImmutable());

        return new ValidatedAuthorizationToken('source-id', $connection, $scopes, new DateTimeImmutable('+1 hour'));
    }
}
