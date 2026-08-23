<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications;

use Mockery;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResource;
use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResourceQueryServiceInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertifications;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertificationsInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertificationsInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertificationsOutput;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SyncOwnedWikiCertificationsTest extends TestCase
{
    public function test__construct(): void
    {
        $resourceQueryService = Mockery::mock(SyncableOwnedWikiResourceQueryServiceInterface::class);
        $updater = Mockery::mock(OfficialResourceUpdaterInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $this->app->instance(SyncableOwnedWikiResourceQueryServiceInterface::class, $resourceQueryService);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $updater);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $useCase = $this->app->make(SyncOwnedWikiCertificationsInterface::class);

        $this->assertInstanceOf(SyncOwnedWikiCertifications::class, $useCase);
    }

    public function testProcessMarksUnmarksAndLeavesUnchangedByTranslationSetIdentifier(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $markId = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $unchangedId = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $rejectId = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $markResource = new SyncableOwnedWikiResource(ResourceType::GROUP, $markId);
        $unchangedResource = new SyncableOwnedWikiResource(ResourceType::SONG, $unchangedId);
        $rejectResource = new SyncableOwnedWikiResource(ResourceType::GROUP, $rejectId);

        $this->registerAuthorizationDependencies($principalIdentifier, true);

        $resourceQueryService = Mockery::mock(SyncableOwnedWikiResourceQueryServiceInterface::class);
        $resourceQueryService->shouldReceive('findSyncableResources')
            ->once()
            ->with($accountIdentifier)
            ->andReturn([$markResource, $unchangedResource, $rejectResource]);
        $resourceQueryService->shouldReceive('findOfficialResources')
            ->once()
            ->with($accountIdentifier, [$markResource, $unchangedResource, $rejectResource])
            ->andReturn([$unchangedResource, $rejectResource]);

        $updater = Mockery::mock(OfficialResourceUpdaterInterface::class);
        $updater->shouldReceive('markOfficial')
            ->once()
            ->with(ResourceType::GROUP, $markId, $accountIdentifier);
        $updater->shouldReceive('unmarkOfficial')
            ->once()
            ->with(ResourceType::GROUP, $rejectId, $accountIdentifier);

        $this->app->instance(SyncableOwnedWikiResourceQueryServiceInterface::class, $resourceQueryService);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $updater);

        $useCase = $this->app->make(SyncOwnedWikiCertificationsInterface::class);
        $output = new SyncOwnedWikiCertificationsOutput();

        $useCase->process(
            new SyncOwnedWikiCertificationsInput(
                $accountIdentifier,
                AccountCategory::AGENCY,
                $principalIdentifier,
                [$markId, $unchangedId],
            ),
            $output,
        );

        $this->assertSame([
            'approved' => [$markResource->toArray()],
            'rejected' => [$rejectResource->toArray()],
            'unchanged' => [$unchangedResource->toArray()],
        ], $output->toArray());
    }

    public function testProcessRejectsNonAgencyAccount(): void
    {
        $resourceQueryService = Mockery::mock(SyncableOwnedWikiResourceQueryServiceInterface::class);
        $resourceQueryService->shouldReceive('findSyncableResources')->never();
        $updater = Mockery::mock(OfficialResourceUpdaterInterface::class);
        $this->app->instance(SyncableOwnedWikiResourceQueryServiceInterface::class, $resourceQueryService);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $updater);
        $this->app->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $useCase = $this->app->make(SyncOwnedWikiCertificationsInterface::class);

        $this->expectException(DisallowedException::class);

        $useCase->process(
            new SyncOwnedWikiCertificationsInput(
                new AccountIdentifier(StrTestHelper::generateUuid()),
                AccountCategory::TALENT,
                new PrincipalIdentifier(StrTestHelper::generateUuid()),
                [],
            ),
            new SyncOwnedWikiCertificationsOutput(),
        );
    }

    public function testProcessRejectsOutOfScopeTranslationSetIdentifierWithoutUpdating(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $syncableId = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $outOfScopeId = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $syncableResource = new SyncableOwnedWikiResource(ResourceType::GROUP, $syncableId);

        $this->registerAuthorizationDependencies($principalIdentifier, true);

        $resourceQueryService = Mockery::mock(SyncableOwnedWikiResourceQueryServiceInterface::class);
        $resourceQueryService->shouldReceive('findSyncableResources')
            ->once()
            ->with($accountIdentifier)
            ->andReturn([$syncableResource]);
        $resourceQueryService->shouldReceive('findOfficialResources')->never();

        $updater = Mockery::mock(OfficialResourceUpdaterInterface::class);
        $updater->shouldReceive('markOfficial')->never();
        $updater->shouldReceive('unmarkOfficial')->never();

        $this->app->instance(SyncableOwnedWikiResourceQueryServiceInterface::class, $resourceQueryService);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $updater);

        $useCase = $this->app->make(SyncOwnedWikiCertificationsInterface::class);

        $this->expectException(DisallowedException::class);

        $useCase->process(
            new SyncOwnedWikiCertificationsInput(
                $accountIdentifier,
                AccountCategory::AGENCY,
                $principalIdentifier,
                [$outOfScopeId],
            ),
            new SyncOwnedWikiCertificationsOutput(),
        );
    }

    public function testProcessRejectsWhenPolicyDenies(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->registerAuthorizationDependencies($principalIdentifier, false);

        $resourceQueryService = Mockery::mock(SyncableOwnedWikiResourceQueryServiceInterface::class);
        $resourceQueryService->shouldReceive('findSyncableResources')->never();
        $resourceQueryService->shouldReceive('findOfficialResources')->never();

        $updater = Mockery::mock(OfficialResourceUpdaterInterface::class);
        $updater->shouldReceive('markOfficial')->never();
        $updater->shouldReceive('unmarkOfficial')->never();

        $this->app->instance(SyncableOwnedWikiResourceQueryServiceInterface::class, $resourceQueryService);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $updater);

        $useCase = $this->app->make(SyncOwnedWikiCertificationsInterface::class);

        $this->expectException(DisallowedException::class);

        $useCase->process(
            new SyncOwnedWikiCertificationsInput(
                $accountIdentifier,
                AccountCategory::AGENCY,
                $principalIdentifier,
                [],
            ),
            new SyncOwnedWikiCertificationsOutput(),
        );
    }

    public function testProcessRejectsWhenPrincipalIsNotFound(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturnNull();

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->never();

        $resourceQueryService = Mockery::mock(SyncableOwnedWikiResourceQueryServiceInterface::class);
        $resourceQueryService->shouldReceive('findSyncableResources')->never();
        $resourceQueryService->shouldReceive('findOfficialResources')->never();

        $updater = Mockery::mock(OfficialResourceUpdaterInterface::class);
        $updater->shouldReceive('markOfficial')->never();
        $updater->shouldReceive('unmarkOfficial')->never();

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(SyncableOwnedWikiResourceQueryServiceInterface::class, $resourceQueryService);
        $this->app->instance(OfficialResourceUpdaterInterface::class, $updater);

        $useCase = $this->app->make(SyncOwnedWikiCertificationsInterface::class);

        $this->expectException(PrincipalNotFoundException::class);

        $useCase->process(
            new SyncOwnedWikiCertificationsInput(
                $accountIdentifier,
                AccountCategory::AGENCY,
                $principalIdentifier,
                [],
            ),
            new SyncOwnedWikiCertificationsOutput(),
        );
    }

    private function registerAuthorizationDependencies(
        PrincipalIdentifier $principalIdentifier,
        bool $policyAllowed,
    ): void {
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()));

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with(
                $principal,
                Action::OFFICIAL_CERTIFICATION_OWNED_WIKI_SYNC,
                Mockery::on(static fn (Resource $resource): bool => $resource->type() === ResourceType::AGENCY
                    && $resource->requesterAccountCategory() === AccountCategory::AGENCY),
            )
            ->andReturn($policyAllowed);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }
}
