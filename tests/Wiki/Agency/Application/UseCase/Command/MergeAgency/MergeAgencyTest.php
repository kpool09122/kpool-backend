<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\MergeAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\UseCase\Command\MergeAgency\MergeAgency;
use Source\Wiki\Agency\Application\UseCase\Command\MergeAgency\MergeAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\MergeAgency\MergeAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MergeAgencyTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $mergeAgency = $this->app->make(MergeAgencyInterface::class);
        $this->assertInstanceOf(MergeAgency::class, $mergeAgency);
    }

    /**
     * 正常系：正しくAgency Entityがマージされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $dummyAgency = $this->createDummyMergeAgency();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new MergeAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyAgency->agency)
            ->andReturn(null);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $mergeAgency = $this->app->make(MergeAgencyInterface::class);
        $agency = $mergeAgency->process($input);
        $this->assertSame((string)$dummyAgency->agencyIdentifier, (string)$agency->agencyIdentifier());
        $this->assertSame((string)$dummyAgency->publishedAgencyIdentifier, (string)$agency->publishedAgencyIdentifier());
        $this->assertSame($dummyAgency->language->value, $agency->language()->value);
        $this->assertSame((string)$dummyAgency->name, (string)$agency->name());
        $this->assertSame((string)$dummyAgency->CEO, (string)$agency->CEO());
        $this->assertSame($dummyAgency->foundedIn->value(), $agency->foundedIn()->value());
        $this->assertSame((string)$dummyAgency->description, (string)$agency->description());
        $this->assertSame($principalIdentifier, $agency->mergerIdentifier());
        $this->assertSame($mergedAt, $agency->mergedAt());
    }

    /**
     * 異常系：指定したIDに紐づくAgencyが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundAgency(): void
    {
        $dummyAgency = $this->createDummyMergeAgency();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new MergeAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(AgencyNotFoundException::class);
        $mergeAgency = $this->app->make(MergeAgencyInterface::class);
        $mergeAgency->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyAgency = $this->createDummyMergeAgency();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new MergeAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(PrincipalNotFoundException::class);
        $mergeAgency = $this->app->make(MergeAgencyInterface::class);
        $mergeAgency->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORがAgencyをマージできること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $dummyAgency = $this->createDummyMergeAgency();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $agencyId = (string) $dummyAgency->agencyIdentifier;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new MergeAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);
        $draftAgencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyAgency->agency)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $mergeAgency = $this->app->make(MergeAgencyInterface::class);
        $agency = $mergeAgency->process($input);
        $this->assertSame($principalIdentifier, $agency->mergerIdentifier());
        $this->assertSame($mergedAt, $agency->mergedAt());
    }

    /**
     * 異常系：COLLABORATORがAgencyをマージしようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $dummyAgency = $this->createDummyMergeAgency();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);

        $input = new MergeAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $this->expectException(UnauthorizedException::class);
        $mergeAgency = $this->app->make(MergeAgencyInterface::class);
        $mergeAgency->process($input);
    }

    /**
     * 異常系：NONEロールがAgencyをマージしようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummyAgency = $this->createDummyMergeAgency();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new MergeAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            $dummyAgency->foundedIn,
            $dummyAgency->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);

        $this->expectException(UnauthorizedException::class);
        $mergeAgency = $this->app->make(MergeAgencyInterface::class);
        $mergeAgency->process($input);
    }

    /**
     * 正常系：foundedInがnullの場合でもマージが成功すること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNullFoundedIn(): void
    {
        $dummyAgency = $this->createDummyMergeAgencyWithNullFoundedIn();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new MergeAgencyInput(
            $dummyAgency->agencyIdentifier,
            $dummyAgency->name,
            $dummyAgency->CEO,
            null,
            $dummyAgency->description,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('save')
            ->once()
            ->with($dummyAgency->agency)
            ->andReturn(null);
        $draftAgencyRepository->shouldReceive('findById')
            ->once()
            ->with($dummyAgency->agencyIdentifier)
            ->andReturn($dummyAgency->agency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $mergeAgency = $this->app->make(MergeAgencyInterface::class);
        $agency = $mergeAgency->process($input);
        $this->assertNull($agency->foundedIn());
        $this->assertSame($principalIdentifier, $agency->mergerIdentifier());
        $this->assertSame($mergedAt, $agency->mergedAt());
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return MergeAgencyTestData
     */
    private function createDummyMergeAgency(): MergeAgencyTestData
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description(<<<'DESC'
### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다.
DESC);

        $status = ApprovalStatus::Pending;
        $agency = new DraftAgency(
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
        );

        return new MergeAgencyTestData(
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
        );
    }

    /**
     * ダミーデータを作成するヘルパーメソッド(foundedInがnull)
     *
     * @return MergeAgencyTestData
     */
    private function createDummyMergeAgencyWithNullFoundedIn(): MergeAgencyTestData
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = null;
        $description = new Description(<<<'DESC'
### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다.
DESC);

        $status = ApprovalStatus::Pending;
        $agency = new DraftAgency(
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
        );

        return new MergeAgencyTestData(
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
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class MergeAgencyTestData
{
    public function __construct(
        public AgencyIdentifier         $agencyIdentifier,
        public AgencyIdentifier         $publishedAgencyIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public AgencyName               $name,
        public string                   $normalizedName,
        public CEO                      $CEO,
        public string                   $normalizedCEO,
        public ?FoundedIn               $foundedIn,
        public Description              $description,
        public ApprovalStatus           $status,
        public DraftAgency              $agency,
    ) {
    }
}
