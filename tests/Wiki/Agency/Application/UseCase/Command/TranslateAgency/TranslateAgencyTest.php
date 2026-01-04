<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Service\TranslationServiceInterface;
use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgency;
use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgencyInput;
use Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency\TranslateAgencyInterface;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
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
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateAgencyTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $agencyService = Mockery::mock(TranslationServiceInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $this->assertInstanceOf(TranslateAgency::class, $translateAgency);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $dummyTranslateAgency = $this->createDummyTranslateAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn($dummyTranslateAgency->agency);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('save')
            ->with($dummyTranslateAgency->enAgency)
            ->once()
            ->andReturn(null);
        $draftAgencyRepository->shouldReceive('save')
            ->with($dummyTranslateAgency->jaAgency)
            ->once()
            ->andReturn(null);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);
        $agencyService->shouldReceive('translateAgency')
            ->with($dummyTranslateAgency->agency, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateAgency->enAgency);
        $agencyService->shouldReceive('translateAgency')
            ->with($dummyTranslateAgency->agency, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateAgency->jaAgency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $agencies = $translateAgency->process($input);
        $this->assertCount(2, $agencies);
        $this->assertSame($dummyTranslateAgency->jaAgency, $agencies[0]);
        $this->assertSame($dummyTranslateAgency->enAgency, $agencies[1]);
    }

    /**
     * 異常系： 指定したIDの事務所情報が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     */
    public function testWhenAgencyNotFound(): void
    {
        $dummyTranslateAgency = $this->createDummyTranslateAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn(null);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(AgencyNotFoundException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
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
        $dummyTranslateAgency = $this->createDummyTranslateAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn($dummyTranslateAgency->agency);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(PrincipalNotFoundException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * 異常系：翻訳権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $dummyTranslateAgency = $this->createDummyTranslateAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn($dummyTranslateAgency->agency);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(UnauthorizedException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * 正常系：ADMINISTRATORが事務所を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAdministrator(): void
    {
        $dummyTranslateAgency = $this->createDummyTranslateAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn($dummyTranslateAgency->agency);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('save')
            ->with($dummyTranslateAgency->enAgency)
            ->once()
            ->andReturn(null);
        $draftAgencyRepository->shouldReceive('save')
            ->with($dummyTranslateAgency->jaAgency)
            ->once()
            ->andReturn(null);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);
        $agencyService->shouldReceive('translateAgency')
            ->with($dummyTranslateAgency->agency, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateAgency->enAgency);
        $agencyService->shouldReceive('translateAgency')
            ->with($dummyTranslateAgency->agency, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateAgency->jaAgency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $result = $translateAgency->process($input);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(DraftAgency::class, $result[0]);
        $this->assertInstanceOf(DraftAgency::class, $result[1]);
    }

    /**
     * 異常系：TALENT_ACTORが事務所情報を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedTalentActor(): void
    {
        $dummyTranslateAgency = $this->createDummyTranslateAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, null, [$groupId], [$talentId]);

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn($dummyTranslateAgency->agency);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(UnauthorizedException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * 異常系：AGENCY_ACTORが他の事務所の事務所情報を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $dummyTranslateAgency = $this->createDummyTranslateAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $anotherAgencyId = StrTestHelper::generateUuid();
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $anotherAgencyId, [], []);

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn($dummyTranslateAgency->agency);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(UnauthorizedException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の事務所の事務所情報を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $dummyTranslateAgency = $this->createDummyTranslateAgency($agencyId);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn($dummyTranslateAgency->agency);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('save')
            ->with($dummyTranslateAgency->enAgency)
            ->once()
            ->andReturn(null);
        $draftAgencyRepository->shouldReceive('save')
            ->with($dummyTranslateAgency->jaAgency)
            ->once()
            ->andReturn(null);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);
        $agencyService->shouldReceive('translateAgency')
            ->with($dummyTranslateAgency->agency, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateAgency->enAgency);
        $agencyService->shouldReceive('translateAgency')
            ->with($dummyTranslateAgency->agency, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateAgency->jaAgency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $result = $translateAgency->process($input);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(DraftAgency::class, $result[0]);
        $this->assertInstanceOf(DraftAgency::class, $result[1]);
    }

    /**
     * 正常系：SENIOR_COLLABORATORが事務所を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyTranslateAgency = $this->createDummyTranslateAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::SENIOR_COLLABORATOR, null, [], []);

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn($dummyTranslateAgency->agency);

        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $draftAgencyRepository->shouldReceive('save')
            ->with($dummyTranslateAgency->enAgency)
            ->once()
            ->andReturn(null);
        $draftAgencyRepository->shouldReceive('save')
            ->with($dummyTranslateAgency->jaAgency)
            ->once()
            ->andReturn(null);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);
        $agencyService->shouldReceive('translateAgency')
            ->with($dummyTranslateAgency->agency, Language::ENGLISH)
            ->once()
            ->andReturn($dummyTranslateAgency->enAgency);
        $agencyService->shouldReceive('translateAgency')
            ->with($dummyTranslateAgency->agency, Language::JAPANESE)
            ->once()
            ->andReturn($dummyTranslateAgency->jaAgency);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $result = $translateAgency->process($input);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(DraftAgency::class, $result[0]);
        $this->assertInstanceOf(DraftAgency::class, $result[1]);
    }

    /**
     * 異常系：NONEロールが事務所を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws AgencyNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $dummyTranslateAgency = $this->createDummyTranslateAgency();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new TranslateAgencyInput(
            $dummyTranslateAgency->agencyIdentifier,
            $dummyTranslateAgency->publishedAgencyIdentifier,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findById')
            ->with($dummyTranslateAgency->agencyIdentifier)
            ->once()
            ->andReturn($dummyTranslateAgency->agency);

        $agencyService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $draftAgencyRepository = Mockery::mock(DraftAgencyRepositoryInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $agencyService);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $this->app->instance(DraftAgencyRepositoryInterface::class, $draftAgencyRepository);
        $this->expectException(UnauthorizedException::class);
        $translateAgency = $this->app->make(TranslateAgencyInterface::class);
        $translateAgency->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $agencyId
     * @return TranslateAgencyTestData
     */
    private function createDummyTranslateAgency(
        ?string $agencyId = null,
    ): TranslateAgencyTestData {
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUuid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description(<<<'DESC'
### JYP엔터테인먼트 (JYP Entertainment)
가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다. HYBE, SM, YG엔터테인먼트와 함께 한국 연예계를 이끄는 **'BIG4'** 중 하나로 꼽힙니다.
DESC);
        $version = new Version(1);

        $agency = new Agency(
            $agencyIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
            $description,
            $version,
        );

        $jaAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::JAPANESE,
            new AgencyName('JYPエンターテインメント'),
            'jypえんたーていんめんと',
            new CEO('J.Y. Park'),
            'j.y. park',
            $foundedIn,
            new Description('### JYPエンターテインメント (JYP Entertainment)'),
            ApprovalStatus::Pending
        );

        $enAgency = new DraftAgency(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::ENGLISH,
            new AgencyName('JYP Entertainment'),
            'jyp entertainment',
            new CEO('J.Y. Park'),
            'j.y. park',
            $foundedIn,
            new Description('### JYP Entertainment'),
            ApprovalStatus::Pending
        );

        return new TranslateAgencyTestData(
            $agencyIdentifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $CEO,
            $foundedIn,
            $description,
            $version,
            $agency,
            $jaAgency,
            $enAgency,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class TranslateAgencyTestData
{
    public function __construct(
        public AgencyIdentifier $agencyIdentifier,
        public AgencyIdentifier $publishedAgencyIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier $editorIdentifier,
        public Language $language,
        public AgencyName $name,
        public CEO $CEO,
        public FoundedIn $foundedIn,
        public Description $description,
        public Version $version,
        public Agency $agency,
        public DraftAgency $jaAgency,
        public DraftAgency $enAgency,
    ) {
    }
}
