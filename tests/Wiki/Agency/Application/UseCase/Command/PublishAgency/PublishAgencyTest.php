<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\PublishAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Application\UseCase\Command\PublishAgency\PublishAgency;
use Source\Wiki\Agency\Application\UseCase\Command\PublishAgency\PublishAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\PublishAgency\PublishAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\AgencyHistory;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyHistoryRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AgencyServiceInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyHistoryIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishAgencyTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $agencyFactory = Mockery::mock(AgencyFactoryInterface::class);
        $this->app->instance(AgencyFactoryInterface::class, $agencyFactory);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $this->assertInstanceOf(PublishAgency::class, $publishAgency);
    }

    /**
     * 正常系：正しく変更されたAgencyが公開されること（すでに一度公開されたことがある場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWhenAlreadyPublished(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $dummyPublishAgency = $this->createDummyPublishAgency(
            hasPublishedAgency: true,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            $dummyPublishAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishAgency->publishedAgencyIdentifier)
            ->andReturn($dummyPublishAgency->publishedAgency);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->publishedAgency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishAgency->agency)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishAgency->history);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->history)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $result = $publishAgency->process($input);
        $this->assertSame((string) $dummyPublishAgency->publishedAgencyIdentifier, (string) $result->agencyIdentifier());
        $this->assertSame($dummyPublishAgency->language->value, $result->language()->value);
        $this->assertSame((string) $dummyPublishAgency->name, (string) $result->name());
        $this->assertSame($dummyPublishAgency->normalizedName, $result->normalizedName());
        $this->assertSame((string) $dummyPublishAgency->CEO, (string) $result->CEO());
        $this->assertSame($dummyPublishAgency->normalizedCEO, $result->normalizedCEO());
        $this->assertSame($dummyPublishAgency->foundedIn->value(), $result->foundedIn()->value());
        $this->assertSame((string) $dummyPublishAgency->description, (string) $result->description());
        $this->assertSame($dummyPublishAgency->exVersion->value() + 1, $result->version()->value());
    }

    /**
     * 正常系：正しく変更されたAgencyが公開されること（初めて公開する場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessForTheFirstTime(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $dummyPublishAgency = $this->createDummyPublishAgency(
            hasPublishedAgency: false,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            $dummyPublishAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->createdAgency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishAgency->agency)
            ->andReturn(null);

        $agencyFactory = Mockery::mock(AgencyFactoryInterface::class);
        $agencyFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->language, $dummyPublishAgency->name)
            ->andReturn($dummyPublishAgency->createdAgency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishAgency->history);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->history)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $result = $publishAgency->process($input);
        $this->assertSame((string) $dummyPublishAgency->publishedAgencyIdentifier, (string) $result->agencyIdentifier());
        $this->assertSame($dummyPublishAgency->language->value, $result->language()->value);
        $this->assertSame((string) $dummyPublishAgency->name, (string) $result->name());
        $this->assertSame((string) $dummyPublishAgency->CEO, (string) $result->CEO());
        $this->assertSame($dummyPublishAgency->foundedIn->value(), $result->foundedIn()->value());
        $this->assertSame((string) $dummyPublishAgency->description, (string) $result->description());
        $this->assertSame($dummyPublishAgency->version->value(), $result->version()->value());
    }

    /**
     * 異常系：指定したIDに紐づくAgencyが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundAgency(): void
    {
        $dummyPublishAgency = $this->createDummyPublishAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            $dummyPublishAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->expectException(AgencyNotFoundException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     */
    public function testInvalidStatus(): void
    {
        $dummyPublishAgency = $this->createDummyPublishAgency(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            $dummyPublishAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->expectException(InvalidStatusException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みの事務所がある場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testHasApprovedButNotTranslatedAgency(): void
    {
        $dummyPublishAgency = $this->createDummyPublishAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            $dummyPublishAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->agencyIdentifier)
            ->andReturn(true);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->expectException(ExistsApprovedButNotTranslatedAgencyException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：公開されている事務所情報が取得できない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPublishedAgency(): void
    {
        $dummyPublishAgency = $this->createDummyPublishAgency(hasPublishedAgency: true);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            $dummyPublishAgency->publishedAgencyIdentifier,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);
        $agencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyPublishAgency->publishedAgencyIdentifier)
            ->andReturn(null);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->expectException(AgencyNotFoundException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：公開権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedRole(): void
    {
        $dummyPublishAgency = $this->createDummyPublishAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::COLLABORATOR, null, [], []);

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            null,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);
        $this->expectException(UnauthorizedException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 正常系：ADMINISTRATORが事務所を公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithAdministrator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::ADMINISTRATOR, null, [], []);

        $dummyPublishAgency = $this->createDummyPublishAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            null,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->createdAgency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishAgency->agency)
            ->andReturn(null);

        $agencyFactory = Mockery::mock(AgencyFactoryInterface::class);
        $agencyFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->language, $dummyPublishAgency->name)
            ->andReturn($dummyPublishAgency->createdAgency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishAgency->history);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->history)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $result = $publishAgency->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $dummyPublishAgency->status);
    }

    /**
     * 異常系：AGENCY_ACTORが他の事務所を公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyPublishAgency = $this->createDummyPublishAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            null,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の事務所を公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $dummyPublishAgency = $this->createDummyPublishAgency(
            agencyId: $agencyId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            null,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->createdAgency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishAgency->agency)
            ->andReturn(null);

        $agencyFactory = Mockery::mock(AgencyFactoryInterface::class);
        $agencyFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->language, $dummyPublishAgency->name)
            ->andReturn($dummyPublishAgency->createdAgency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishAgency->history);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->history)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：GROUP_ACTORが事務所を公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedGroupActor(): void
    {
        $dummyPublishAgency = $this->createDummyPublishAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::GROUP_ACTOR, null, [$groupId], []);

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            null,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：TALENT_ACTORが事務所を公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedTalentActor(): void
    {
        $dummyPublishAgency = $this->createDummyPublishAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::TALENT_ACTOR, null, [$groupId], [$talentId]);

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            null,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORが事務所を公開できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $dummyPublishAgency = $this->createDummyPublishAgency(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            null,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);
        $agencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->createdAgency)
            ->andReturn(null);
        $agencyRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($dummyPublishAgency->agency)
            ->andReturn(null);

        $agencyFactory = Mockery::mock(AgencyFactoryInterface::class);
        $agencyFactory->shouldReceive('create')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->language, $dummyPublishAgency->name)
            ->andReturn($dummyPublishAgency->createdAgency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyService->shouldReceive('existsApprovedButNotTranslatedAgency')
            ->once()
            ->with($dummyPublishAgency->translationSetIdentifier, $dummyPublishAgency->agencyIdentifier)
            ->andReturn(false);

        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);
        $agencyHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($dummyPublishAgency->history);

        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryRepository->shouldReceive('save')
            ->once()
            ->with($dummyPublishAgency->history)
            ->andReturn(null);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyFactoryInterface::class, $agencyFactory);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * 異常系：NONEロールが事務所を公開しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyPublishAgency = $this->createDummyPublishAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUlid()), Role::NONE, null, [], []);

        $input = new PublishAgencyInput(
            $dummyPublishAgency->agencyIdentifier,
            null,
            $principal,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftById')
            ->once()
            ->with($dummyPublishAgency->agencyIdentifier)
            ->andReturn($dummyPublishAgency->agency);

        $agencyService = Mockery::mock(AgencyServiceInterface::class);
        $agencyHistoryRepository = Mockery::mock(AgencyHistoryRepositoryInterface::class);
        $agencyHistoryFactory = Mockery::mock(AgencyHistoryFactoryInterface::class);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(AgencyServiceInterface::class, $agencyService);
        $this->app->instance(AgencyHistoryRepositoryInterface::class, $agencyHistoryRepository);
        $this->app->instance(AgencyHistoryFactoryInterface::class, $agencyHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $publishAgency = $this->app->make(PublishAgencyInterface::class);
        $publishAgency->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $agencyId
     * @param ApprovalStatus $status
     * @param bool $hasPublishedAgency
     * @param EditorIdentifier|null $operatorIdentifier
     * @return PublishAgencyTestData
     */
    private function createDummyPublishAgency(
        ?string $agencyId = null,
        ApprovalStatus $status = ApprovalStatus::UnderReview,
        bool $hasPublishedAgency = false,
        ?EditorIdentifier $operatorIdentifier = null,
    ): PublishAgencyTestData {
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUlid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'JYPㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description(<<<'DESC'
### JYP엔터테インメント (JYP Entertainment)
가수 겸 음악 프로デュー서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인メント 기업입니다。HYBE, SM, YG엔터테인먼트と 함께 한국 연예계를 이끄는 **'BIG4'** 중 하나로 꼽힙니다。
**'진실, 성실, 겸손'**이라는 가치관を 매우 중시하며, 소속 아티ストの 노래やダンス 실력だけではなく 인성을 존중する 육성 방침으로 알려져 있습니다。 이러한 철학は 박진영이 オーディション 프로그램 등에서 보여주는 모습을 통해서도 널리 알려져 있습니다。
음악적인 면では 설립자인 박진영이 직접 プロデューサーとして 多くの曲 작업に 참여しており、대중에게 사랑받는 캐ッチ한 히트곡を 수많이 만들어왔습니다。
---
### 주요 소속 아ーティスト
지금까지 **원더걸ス(Wonder Girls)**, **2PM**, **ミ쓰에이(Miss A)**와 같이 K팝의 역사를 만들어 온 그룹들을 배출してきました。
현재도
* **트와이스 (TWICE)**
* **스트레이 キ즈 (Stray Kids)**
* **있지 (ITZY)**
* **엔믹스 (NMIXX)**
등 세계적인 인기를 자랑하는 グループが 다수 所属되어 있으며、K팝의 グローバル한 발전에서 중심적인 역할을 계속해서 맡고 있습니다。음악 사업 외に 배우 マネジメントや 공연 事業도 하고 있습니다。
DESC);

        $agency = new DraftAgency(
            $agencyIdentifier,
            $hasPublishedAgency ? $publishedAgencyIdentifier : null,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
            $description,
            $status,
        );

        // 既存の公開済み事務所（更新時用）
        $exName = new AgencyName('HYBE');
        $exCEO = new CEO('이재상');
        $exFoundedIn = new FoundedIn(new DateTimeImmutable('2005-02-01'));
        $exDescription = new Description('HYBE의 가장 큰 특징은 단순한 연예 기획사가 아니라 **\'음악 산업의 혁신\'**을 목표로 하는 라이프스타일 플랫폼 기업이라는 점입니다. BTS의 세계적인 성공을 기반으로 2021년에 현재의 사명으로 변경했습니다.');
        $exVersion = new Version(1);
        $publishedAgency = new Agency(
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $language,
            $exName,
            'ㅎㅇㅂㅇ',
            $exCEO,
            '이재상',
            $exFoundedIn,
            $exDescription,
            $exVersion,
        );

        // 新規作成用のAgency
        $version = new Version(1);
        $createdAgency = new Agency(
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $normalizedName,
            new CEO(''),
            '',
            null,
            new Description(''),
            $version,
        );

        $historyIdentifier = new AgencyHistoryIdentifier(StrTestHelper::generateUlid());
        $history = new AgencyHistory(
            $historyIdentifier,
            $operatorIdentifier ?? new EditorIdentifier(StrTestHelper::generateUlid()),
            $agency->editorIdentifier(),
            $hasPublishedAgency ? $publishedAgencyIdentifier : null,
            $agency->agencyIdentifier(),
            $agency->status(),
            null,
            $agency->name(),
            new DateTimeImmutable('now'),
        );

        return new PublishAgencyTestData(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
            $description,
            $status,
            $agency,
            $publishedAgency,
            $createdAgency,
            $version,
            $exVersion,
            $historyIdentifier,
            $history,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class PublishAgencyTestData
{
    public function __construct(
        public AgencyIdentifier $agencyIdentifier,
        public AgencyIdentifier $publishedAgencyIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public EditorIdentifier $editorIdentifier,
        public Language $language,
        public AgencyName $name,
        public string $normalizedName,
        public CEO $CEO,
        public string $normalizedCEO,
        public FoundedIn $foundedIn,
        public Description $description,
        public ApprovalStatus $status,
        public DraftAgency $agency,
        public Agency $publishedAgency,
        public Agency $createdAgency,
        public Version $version,
        public Version $exVersion,
        public AgencyHistoryIdentifier $historyIdentifier,
        public AgencyHistory $history,
    ) {
    }
}
