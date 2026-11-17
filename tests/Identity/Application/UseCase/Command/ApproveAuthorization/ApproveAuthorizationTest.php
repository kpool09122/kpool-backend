<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\ApproveAuthorization;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Application\Service\AuthorizationAccount;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConnectionServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Application\Service\PendingAuthorization;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorization;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationInput;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationOutput;
use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Identity\Domain\Factory\AuthorizationConnectionFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\Repository\AuthorizationScopeSettingsRepositoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationScopeSettingsIdentifier;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class ApproveAuthorizationTest extends TestCase
{
    /** @return array<string, array{list<AuthorizationScope>|null, AuthorizationScope}> */
    public static function rejectedScopes(): array
    {
        return [
            'outside requested scopes' => [AuthorizationScope::cases(), AuthorizationScope::DraftWrite],
            'not configured' => [null, AuthorizationScope::WikiRead],
            'all disabled' => [[], AuthorizationScope::WikiRead],
            'not explicitly allowed' => [[AuthorizationScope::DraftWrite], AuthorizationScope::WikiRead],
        ];
    }

    /** @param list<AuthorizationScope>|null $allowed */
    #[DataProvider('rejectedScopes')]
    public function testRejectsDisallowedScopesBeforePersisting(?array $allowed, AuthorizationScope $selected): void
    {
        $identity = new IdentityIdentifier((string) Str::uuid7());
        $account = new AccountIdentifier((string) Str::uuid7());
        $parameters = new AuthorizationCodeParameters(new OAuthClientIdentifier((string) Str::uuid7()), 'https://client.test/cb', [AuthorizationScope::WikiRead], 'https://mcp.test', str_repeat('a', 43), 'state');
        /** @var AuthorizationRequestStorageServiceInterface&MockInterface $storage */
        $storage = Mockery::mock(AuthorizationRequestStorageServiceInterface::class);
        $storage->shouldReceive('requireValid')->once()->with('request', $identity)->andReturn(new PendingAuthorization('request', $parameters, new DateTimeImmutable('+10 minutes'), $identity));
        /** @var AuthorizationRequestValidatorInterface&MockInterface $validator */
        $validator = Mockery::mock(AuthorizationRequestValidatorInterface::class);
        $validator->shouldReceive('validate')->once()->with($parameters)->andReturn('Client');
        /** @var AuthorizationAccountServiceInterface&MockInterface $accounts */
        $accounts = Mockery::mock(AuthorizationAccountServiceInterface::class);
        $originalAccount = new AuthorizationAccount($identity, $account, new AccountPrincipalIdentifier((string) Str::uuid7()), 'Original');
        $accounts->shouldReceive('resolveOriginalAccount')->once()->with($identity)->andReturn($originalAccount);
        /** @var AuthorizationConnectionRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(AuthorizationConnectionRepositoryInterface::class);
        $repository->shouldNotReceive('save');
        /** @var AuthorizationConnectionFactoryInterface&MockInterface $factory */
        $factory = Mockery::mock(AuthorizationConnectionFactoryInterface::class);
        $factory->shouldNotReceive('create');
        /** @var AuthorizationConnectionServiceInterface&MockInterface $codes */
        $codes = Mockery::mock(AuthorizationConnectionServiceInterface::class);
        $codes->shouldNotReceive('issueCode');
        /** @var AuthorizationScopeSettingsRepositoryInterface&MockInterface $settings */
        $settings = Mockery::mock(AuthorizationScopeSettingsRepositoryInterface::class);
        $settings->shouldReceive('find')->once()->with($identity)->andReturn($allowed === null ? null : new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier('0199f700-0000-7000-8000-000000000098'), $identity, $allowed));
        $useCase = new ApproveAuthorization($storage, $validator, $accounts, $factory, $repository, $codes, $settings);
        $this->expectException(InvalidAuthorizationRequestException::class);
        $useCase->process(new ApproveAuthorizationInput('request', $identity, [$selected]), new ApproveAuthorizationOutput());
    }
}
