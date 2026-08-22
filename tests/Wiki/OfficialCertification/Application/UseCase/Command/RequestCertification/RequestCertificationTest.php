<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertification;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationOutput;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Factory\OfficialCertificationFactoryInterface;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestCertificationTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $factory = Mockery::mock(OfficialCertificationFactoryInterface::class);
        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialCertificationFactoryInterface::class, $factory);

        $useCase = $this->app->make(RequestCertificationInterface::class);

        $this->assertInstanceOf(RequestCertification::class, $useCase);
    }

    public function testProcess(): void
    {
        $certificationId = StrTestHelper::generateUuid();
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());
        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $certification = new OfficialCertification(
            new CertificationIdentifier($certificationId),
            ResourceType::AGENCY,
            $wikiId,
            $ownerAccountIdentifier,
            CertificationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findByResource')
            ->once()
            ->with(ResourceType::AGENCY, $wikiId)
            ->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->with($certification)
            ->andReturnNull();

        $factory = Mockery::mock(OfficialCertificationFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with(ResourceType::AGENCY, $wikiId, $ownerAccountIdentifier)
            ->andReturn($certification);

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialCertificationFactoryInterface::class, $factory);
        $this->registerAuthorizationDependencies($wikiId, $ownerAccountIdentifier, $principalIdentifier, AccountCategory::AGENCY, ResourceType::AGENCY, true);

        $useCase = $this->app->make(RequestCertificationInterface::class);

        $input = new RequestCertificationInput(
            ResourceType::AGENCY,
            $wikiId,
            $ownerAccountIdentifier,
            $principalIdentifier,
        );

        $output = new RequestCertificationOutput();

        $useCase->process($input, $output);

        $result = $output->toArray();

        $this->assertSame($certificationId, $result['certificationIdentifier']);
        $this->assertSame(CertificationStatus::PENDING->value, $result['status']);
    }

    public function testProcessWhenAlreadyRequested(): void
    {
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());
        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $existing = new OfficialCertification(
            new CertificationIdentifier(StrTestHelper::generateUuid()),
            ResourceType::AGENCY,
            $wikiId,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            CertificationStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
        );

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $repository->shouldReceive('findByResource')
            ->once()
            ->with(ResourceType::AGENCY, $wikiId)
            ->andReturn($existing);

        $factory = Mockery::mock(OfficialCertificationFactoryInterface::class);

        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialCertificationFactoryInterface::class, $factory);
        $this->registerAuthorizationDependencies($wikiId, $ownerAccountIdentifier, $principalIdentifier, AccountCategory::AGENCY, ResourceType::AGENCY, true);

        $useCase = $this->app->make(RequestCertificationInterface::class);

        $input = new RequestCertificationInput(
            ResourceType::AGENCY,
            $wikiId,
            $ownerAccountIdentifier,
            $principalIdentifier,
        );

        $output = new RequestCertificationOutput();

        $this->expectException(OfficialCertificationAlreadyRequestedException::class);

        $useCase->process($input, $output);
    }

    public function testProcessWhenAccountCategoryDoesNotMatchResourceType(): void
    {
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());
        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $factory = Mockery::mock(OfficialCertificationFactoryInterface::class);
        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialCertificationFactoryInterface::class, $factory);
        $this->registerAuthorizationDependencies($wikiId, $ownerAccountIdentifier, $principalIdentifier, AccountCategory::GENERAL, ResourceType::AGENCY, false);

        $useCase = $this->app->make(RequestCertificationInterface::class);
        $input = new RequestCertificationInput(ResourceType::AGENCY, $wikiId, $ownerAccountIdentifier, $principalIdentifier);
        $output = new RequestCertificationOutput();

        $this->expectException(DisallowedException::class);

        $useCase->process($input, $output);
    }

    public function testProcessWhenPolicyDenies(): void
    {
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());
        $ownerAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(OfficialCertificationRepositoryInterface::class);
        $factory = Mockery::mock(OfficialCertificationFactoryInterface::class);
        $this->app->instance(OfficialCertificationRepositoryInterface::class, $repository);
        $this->app->instance(OfficialCertificationFactoryInterface::class, $factory);
        $this->registerAuthorizationDependencies($wikiId, $ownerAccountIdentifier, $principalIdentifier, AccountCategory::AGENCY, ResourceType::AGENCY, false);

        $useCase = $this->app->make(RequestCertificationInterface::class);
        $input = new RequestCertificationInput(ResourceType::AGENCY, $wikiId, $ownerAccountIdentifier, $principalIdentifier);
        $output = new RequestCertificationOutput();

        $this->expectException(DisallowedException::class);

        $useCase->process($input, $output);
    }

    private function registerAuthorizationDependencies(
        WikiIdentifier $wikiId,
        AccountIdentifier $ownerAccountIdentifier,
        PrincipalIdentifier $principalIdentifier,
        AccountCategory $accountCategory,
        ResourceType $wikiResourceType,
        bool $policyAllowed,
    ): void {
        $account = Mockery::mock(Account::class);
        $account->shouldReceive('accountCategory')->andReturn($accountCategory);

        $wiki = Mockery::mock(Wiki::class);
        $wiki->shouldReceive('resourceType')->andReturn($wikiResourceType);
        $wiki->shouldReceive('wikiIdentifier')->andReturn($wikiId);
        $wiki->shouldReceive('basic')->andReturn(Mockery::mock(BasicInterface::class));

        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findById')->with($ownerAccountIdentifier)->andReturn($account);

        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->with($wikiId)->andReturn($wiki);

        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()));
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->with($principalIdentifier)->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->with(
                $principal,
                Action::OFFICIAL_CERTIFICATION_REQUEST,
                Mockery::on(static fn (Resource $resource): bool => $resource->ownerAccountCategory() === $accountCategory),
            )
            ->andReturn($policyAllowed);

        $this->app->instance(AccountRepositoryInterface::class, $accountRepository);
        $this->app->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }
}
