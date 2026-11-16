<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\ApproveAuthorization;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Application\Service\AuthorizationAccount;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConsentServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Application\Service\PendingAuthorization;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorization;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationInput;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationOutput;
use Source\Identity\Domain\Factory\AuthorizationConsentFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class ApproveAuthorizationTest extends TestCase
{
    public function testRejectsScopeOutsideRequestedScopesBeforePersisting(): void
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
        /** @var AuthorizationConsentRepositoryInterface&MockInterface $repository */
        $repository = Mockery::mock(AuthorizationConsentRepositoryInterface::class);
        $repository->shouldNotReceive('save');
        /** @var AuthorizationConsentFactoryInterface&MockInterface $factory */
        $factory = Mockery::mock(AuthorizationConsentFactoryInterface::class);
        $factory->shouldNotReceive('create');
        /** @var AuthorizationConsentServiceInterface&MockInterface $codes */
        $codes = Mockery::mock(AuthorizationConsentServiceInterface::class);
        $codes->shouldNotReceive('issueCode');
        $useCase = new ApproveAuthorization($storage, $validator, $accounts, $factory, $repository, $codes);
        $this->expectException(InvalidAuthorizationConsentException::class);
        $useCase->process(new ApproveAuthorizationInput('request', $identity, [AuthorizationScope::DraftWrite]), new ApproveAuthorizationOutput());
    }
}
