<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Service;

use DateTimeImmutable;
use Mockery;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AgencyService;
use Source\Wiki\Agency\Domain\Service\AgencyServiceInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AgencyServiceTest extends TestCase
{
    public function test__construct(): void
    {
        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyService = $this->app->make(AgencyServiceInterface::class);
        $this->assertInstanceOf(AgencyService::class, $agencyService);
    }

    public function testExistsApprovedButNotTranslatedAgencyWhenApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());

        // Approved状態のDraftAgencyを作成（日本語版）
        $approvedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $approvedAgency = new DraftAgency(
            $approvedAgencyIdentifier,
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            $translationSetIdentifier,
            new EditorIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new AgencyName('JYPエンターテインメント'),
            'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ',
            new CEO('J.Y. Park'),
            'j.y. park',
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('歌手兼音楽プロデューサーである**パク・ジニョン（J.Y. Park）**が1997年に設立した韓国の大手総合エンターテインメント企業です。'),
            ApprovalStatus::Approved,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedAgency]);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyService = $this->app->make(AgencyServiceInterface::class);

        $result = $agencyService->existsApprovedButNotTranslatedAgency(
            $translationSetIdentifier,
            $excludeAgencyIdentifier,
        );

        $this->assertTrue($result);
    }

    public function testExistsApprovedButNotTranslatedAgencyWhenNoApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());

        // Pending状態のDraftAgencyを作成（韓国語版）
        $pendingAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $pendingAgency = new DraftAgency(
            $pendingAgencyIdentifier,
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            $translationSetIdentifier,
            new EditorIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ',
            new CEO('J.Y. Park'),
            'j.y. park',
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다.'),
            ApprovalStatus::Pending,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$pendingAgency]);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyService = $this->app->make(AgencyServiceInterface::class);

        $result = $agencyService->existsApprovedButNotTranslatedAgency(
            $translationSetIdentifier,
            $excludeAgencyIdentifier,
        );

        $this->assertFalse($result);
    }

    public function testExistsApprovedButNotTranslatedAgencyWhenOnlySelfIsApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());

        // 自分自身がApproved状態（英語版）
        $agency = new DraftAgency(
            $agencyIdentifier,
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            $translationSetIdentifier,
            new EditorIdentifier(StrTestHelper::generateUuid()),
            Language::ENGLISH,
            new AgencyName('JYP Entertainment'),
            'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ',
            new CEO('J.Y. Park'),
            'j.y. park',
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('JYP Entertainment is a major South Korean entertainment company founded in 1997 by J.Y. Park.'),
            ApprovalStatus::Approved,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$agency]);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyService = $this->app->make(AgencyServiceInterface::class);

        $result = $agencyService->existsApprovedButNotTranslatedAgency(
            $translationSetIdentifier,
            $agencyIdentifier, // 自分自身を除外
        );

        $this->assertFalse($result);
    }

    public function testExistsApprovedButNotTranslatedAgencyWhenNoDrafts(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([]);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyService = $this->app->make(AgencyServiceInterface::class);

        $result = $agencyService->existsApprovedButNotTranslatedAgency(
            $translationSetIdentifier,
            $excludeAgencyIdentifier,
        );

        $this->assertFalse($result);
    }

    public function testExistsApprovedButNotTranslatedAgencyWhenMultipleApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $publishedAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());

        // 複数のApproved状態のDraftAgencyを作成（韓国語版）
        $approvedAgency1Identifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $approvedAgency1 = new DraftAgency(
            $approvedAgency1Identifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            new EditorIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new AgencyName('JYP엔터테인먼트'),
            'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ',
            new CEO('J.Y. Park'),
            'j.y. park',
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('가수 겸 음악 프로듀서인 **박진영(J.Y. Park)**이 1997년에 설립한 한국의 대형 종합 엔터테인먼트 기업입니다.'),
            ApprovalStatus::Approved,
        );

        // 日本語版
        $approvedAgency2Identifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $approvedAgency2 = new DraftAgency(
            $approvedAgency2Identifier,
            $publishedAgencyIdentifier,
            $translationSetIdentifier,
            new EditorIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new AgencyName('JYPエンターテインメント'),
            'ㅈㅇㅍㅇㅌㅌㅇㅁㅌ',
            new CEO('J.Y. Park'),
            'j.y. park',
            new FoundedIn(new DateTimeImmutable('1997-04-25')),
            new Description('歌手兼音楽プロデューサーである**パク・ジニョン（J.Y. Park）**が1997年に設立した韓国の大手総合エンターテインメント企業です。'),
            ApprovalStatus::Approved,
        );

        $agencyRepository = Mockery::mock(AgencyRepositoryInterface::class);
        $agencyRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedAgency1, $approvedAgency2]);

        $this->app->instance(AgencyRepositoryInterface::class, $agencyRepository);
        $agencyService = $this->app->make(AgencyServiceInterface::class);

        $result = $agencyService->existsApprovedButNotTranslatedAgency(
            $translationSetIdentifier,
            $excludeAgencyIdentifier,
        );

        $this->assertTrue($result);
    }
}
