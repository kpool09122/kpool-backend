<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\TalentService;
use Source\Wiki\Talent\Domain\Service\TalentServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentServiceTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $talentService = $this->app->make(TalentServiceInterface::class);
        $this->assertInstanceOf(TalentService::class, $talentService);
    }

    /**
     * 正常系: Approved状態のDraftTalentが存在する場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testExistsApprovedButNotTranslatedTalentWhenApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        // 承認済みのDraftTalent (韓国語版)
        $approvedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $approvedName = new TalentName('채영');
        $approvedRealName = new RealName('손채영');
        $approvedTalent = new DraftTalent(
            $approvedTalentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::KOREAN,
            $approvedName,
            'ㅊㅇ',
            $approvedRealName,
            'ㅅㅊㅇ',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new GroupIdentifier(StrTestHelper::generateUuid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('트와이스 멤버'),
            new ImagePath('/images/chaeyoung.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test'),
            ]),
            ApprovalStatus::Approved,
        );

        // 除外対象のDraftTalent (日本語版)
        $excludeName = new TalentName('チェヨン');
        $excludeRealName = new RealName('ソン・チェヨン');
        $excludeTalent = new DraftTalent(
            $excludeTalentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::JAPANESE,
            $excludeName,
            'ちぇよん',
            $excludeRealName,
            'そんちぇよん',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new GroupIdentifier(StrTestHelper::generateUuid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('TWICEメンバー'),
            new ImagePath('/images/chaeyoung_ja.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test2'),
            ]),
            ApprovalStatus::Pending,
        );

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedTalent, $excludeTalent]);

        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $talentService = $this->app->make(TalentServiceInterface::class);

        $result = $talentService->existsApprovedButNotTranslatedTalent(
            $translationSetIdentifier,
            $excludeTalentIdentifier,
        );

        $this->assertTrue($result);
    }

    /**
     * 正常系: Approved状態のDraftTalentが存在しない場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testExistsApprovedButNotTranslatedTalentWhenNoApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        // Pending状態のDraftTalent (韓国語版)
        $pendingTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $pendingName = new TalentName('채영');
        $pendingRealName = new RealName('손채영');
        $pendingTalent = new DraftTalent(
            $pendingTalentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::KOREAN,
            $pendingName,
            'ㅊㅇ',
            $pendingRealName,
            'ㅅㅊㅇ',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new GroupIdentifier(StrTestHelper::generateUuid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('트와이스 멤버'),
            new ImagePath('/images/chaeyoung.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test'),
            ]),
            ApprovalStatus::Pending,
        );

        // 除外対象のDraftTalent (日本語版)
        $excludeName = new TalentName('チェヨン');
        $excludeRealName = new RealName('ソン・チェヨン');
        $excludeTalent = new DraftTalent(
            $excludeTalentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::JAPANESE,
            $excludeName,
            'ちぇよん',
            $excludeRealName,
            'そんちぇよん',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new GroupIdentifier(StrTestHelper::generateUuid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('TWICEメンバー'),
            new ImagePath('/images/chaeyoung_ja.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test2'),
            ]),
            ApprovalStatus::Pending,
        );

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$pendingTalent, $excludeTalent]);

        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $talentService = $this->app->make(TalentServiceInterface::class);

        $result = $talentService->existsApprovedButNotTranslatedTalent(
            $translationSetIdentifier,
            $excludeTalentIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * 正常系: 自分自身がApprovedでも除外されるのでfalseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testExistsApprovedButNotTranslatedTalentWhenOnlySelfIsApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        // 自分自身 (Approved状態だが除外される)
        $selfName = new TalentName('チェヨン');
        $selfRealName = new RealName('ソン・チェヨン');
        $selfTalent = new DraftTalent(
            $excludeTalentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::JAPANESE,
            $selfName,
            'ちぇよん',
            $selfRealName,
            'そんちぇよん',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new GroupIdentifier(StrTestHelper::generateUuid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('TWICEメンバー'),
            new ImagePath('/images/chaeyoung_ja.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test'),
            ]),
            ApprovalStatus::Approved,
        );

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$selfTalent]);

        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $talentService = $this->app->make(TalentServiceInterface::class);

        $result = $talentService->existsApprovedButNotTranslatedTalent(
            $translationSetIdentifier,
            $excludeTalentIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * 正常系: DraftTalentが存在しない場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedTalentWhenNoDrafts(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([]);

        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $talentService = $this->app->make(TalentServiceInterface::class);

        $result = $talentService->existsApprovedButNotTranslatedTalent(
            $translationSetIdentifier,
            $excludeTalentIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * 正常系: 複数のApproved状態のDraftTalentが存在する場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testExistsApprovedButNotTranslatedTalentWhenMultipleApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        // 韓国語版 (Approved)
        $koreanTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $koreanName = new TalentName('채영');
        $koreanRealName = new RealName('손채영');
        $koreanTalent = new DraftTalent(
            $koreanTalentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::KOREAN,
            $koreanName,
            'ㅊㅇ',
            $koreanRealName,
            'ㅅㅊㅇ',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Approved,
        );

        // 英語版 (Approved)
        $englishTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $englishName = new TalentName('Chaeyoung');
        $englishRealName = new RealName('Son Chaeyoung');
        $englishTalent = new DraftTalent(
            $englishTalentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::ENGLISH,
            $englishName,
            'chaeyoung',
            $englishRealName,
            'son chaeyoung',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Approved,
        );

        // 日本語版 (Pending, 除外対象)
        $japaneseName = new TalentName('チェヨン');
        $japaneseRealName = new RealName('ソン・チェヨン');
        $japaneseTalent = new DraftTalent(
            $excludeTalentIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Language::JAPANESE,
            $japaneseName,
            'ちぇよん',
            $japaneseRealName,
            'そんちぇよん',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Pending,
        );

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$koreanTalent, $englishTalent, $japaneseTalent]);

        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $talentService = $this->app->make(TalentServiceInterface::class);

        $result = $talentService->existsApprovedButNotTranslatedTalent(
            $translationSetIdentifier,
            $excludeTalentIdentifier,
        );

        $this->assertTrue($result);
    }
}
