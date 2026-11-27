<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Monetization\Account\Application\Service\CardGatewayInterface;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PaymentCardFixture
{
    public MonetizationAccountIdentifier $accountId;
    public PrincipalIdentifier $principalId;
    public IdentityIdentifier $identityId;
    public MonetizationAccount $account;
    public MonetizationAccountRepositoryInterface&MockInterface $monetizationAccountRepository;
    public PrincipalRepositoryInterface&MockInterface $principalRepository;
    public PrincipalReachabilityInterface&MockInterface $principalReachability;
    public PolicyEvaluatorInterface&MockInterface $policyEvaluator;
    public StepUpAuthenticationStorageServiceInterface&MockInterface $stepUpAuthenticationStorageService;
    public CardGatewayInterface&MockInterface $cardGateway;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T&MockInterface
     */
    public static function mock(string $class): MockInterface
    {
        /** @var T&MockInterface $mock */
        $mock = Mockery::mock($class);

        return $mock;
    }

    public function __construct(bool $withCustomer = true)
    {
        $this->accountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $this->principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $this->identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $this->account = new MonetizationAccount($this->accountId, new AccountIdentifier(StrTestHelper::generateUuid()), [], $withCustomer ? new PaymentCustomerId('cus_test123') : null, null);
        $this->monetizationAccountRepository = self::mock(MonetizationAccountRepositoryInterface::class);
        $this->principalRepository = self::mock(PrincipalRepositoryInterface::class);
        $this->principalReachability = self::mock(PrincipalReachabilityInterface::class);
        $this->policyEvaluator = self::mock(PolicyEvaluatorInterface::class);
        $this->stepUpAuthenticationStorageService = self::mock(StepUpAuthenticationStorageServiceInterface::class);
        $this->cardGateway = self::mock(CardGatewayInterface::class);
    }

    public function allowAuthorization(Action $action, bool $allowed = true, int $times = 1, ?bool $nextAllowed = null): void
    {
        $this->monetizationAccountRepository->shouldReceive('findByAccountIdentifier')->with($this->account->accountIdentifier())->andReturn($this->account);
        $principal = new Principal($this->principalId, $this->identityId, $this->account->accountIdentifier(), PrincipalStatus::ACTIVE);
        $this->principalRepository->shouldReceive('findById')->with($this->principalId)->times($times)->andReturn($principal);
        $this->principalReachability->shouldReceive('canAccess')->with($this->identityId, $this->account->accountIdentifier())->times($times)->andReturnTrue();
        $this->policyEvaluator->shouldReceive('evaluate')->with($principal, $action, Mockery::on(fn (Resource $resource): bool => $resource->type() === ResourceType::PAYMENT_METHOD && (string) $resource->accountIdentifier() === (string) $this->account->accountIdentifier()))->times($times)->andReturn(...($nextAllowed === null ? [$allowed] : [$allowed, $nextAllowed]));
    }

    public function allowStepUp(): void
    {
        $this->stepUpAuthenticationStorageService->shouldReceive('requireValid')->with($this->identityId, StepUpAuthenticationScope::RECENT_AUTHENTICATION)->once()->andReturn(new StepUpAuthentication($this->identityId, StepUpAuthenticationMethod::PASSKEY, new DateTimeImmutable(), StepUpAuthenticationScope::RECENT_AUTHENTICATION, new DateTimeImmutable('+5 minutes')));
    }
}
