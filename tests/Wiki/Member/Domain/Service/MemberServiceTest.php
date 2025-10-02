<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Domain\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Member\Domain\Service\MemberService;
use Source\Wiki\Member\Domain\Service\MemberServiceInterface;
use Source\Wiki\Member\Domain\ValueObject\Birthday;
use Source\Wiki\Member\Domain\ValueObject\Career;
use Source\Wiki\Member\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Member\Domain\ValueObject\RealName;
use Source\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MemberServiceTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $memberService = $this->app->make(MemberServiceInterface::class);
        $this->assertInstanceOf(MemberService::class, $memberService);
    }

    /**
     * 正常系: Approved状態のDraftMemberが存在する場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testExistsApprovedButNotTranslatedMemberWhenApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $excludeMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());

        // 承認済みのDraftMember (韓国語版)
        $approvedMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $approvedMember = new DraftMember(
            $approvedMemberIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::KOREAN,
            new MemberName('채영'),
            new RealName('손채영'),
            [
                new GroupIdentifier(StrTestHelper::generateUlid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('트와이스 멤버'),
            new ImagePath('/images/chaeyoung.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test'),
            ]),
            ApprovalStatus::Approved,
        );

        // 除外対象のDraftMember (日本語版)
        $excludeMember = new DraftMember(
            $excludeMemberIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::JAPANESE,
            new MemberName('チェヨン'),
            new RealName('ソン・チェヨン'),
            [
                new GroupIdentifier(StrTestHelper::generateUlid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('TWICEメンバー'),
            new ImagePath('/images/chaeyoung_ja.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test2'),
            ]),
            ApprovalStatus::Pending,
        );

        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $memberRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedMember, $excludeMember]);

        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $memberService = $this->app->make(MemberServiceInterface::class);

        $result = $memberService->existsApprovedButNotTranslatedMember(
            $translationSetIdentifier,
            $excludeMemberIdentifier,
        );

        $this->assertTrue($result);
    }

    /**
     * 正常系: Approved状態のDraftMemberが存在しない場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testExistsApprovedButNotTranslatedMemberWhenNoApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $excludeMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());

        // Pending状態のDraftMember (韓国語版)
        $pendingMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $pendingMember = new DraftMember(
            $pendingMemberIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::KOREAN,
            new MemberName('채영'),
            new RealName('손채영'),
            [
                new GroupIdentifier(StrTestHelper::generateUlid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('트와이스 멤버'),
            new ImagePath('/images/chaeyoung.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test'),
            ]),
            ApprovalStatus::Pending,
        );

        // 除外対象のDraftMember (日本語版)
        $excludeMember = new DraftMember(
            $excludeMemberIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::JAPANESE,
            new MemberName('チェヨン'),
            new RealName('ソン・チェヨン'),
            [
                new GroupIdentifier(StrTestHelper::generateUlid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('TWICEメンバー'),
            new ImagePath('/images/chaeyoung_ja.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test2'),
            ]),
            ApprovalStatus::Pending,
        );

        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $memberRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$pendingMember, $excludeMember]);

        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $memberService = $this->app->make(MemberServiceInterface::class);

        $result = $memberService->existsApprovedButNotTranslatedMember(
            $translationSetIdentifier,
            $excludeMemberIdentifier,
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
    public function testExistsApprovedButNotTranslatedMemberWhenOnlySelfIsApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $excludeMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());

        // 自分自身 (Approved状態だが除外される)
        $selfMember = new DraftMember(
            $excludeMemberIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::JAPANESE,
            new MemberName('チェヨン'),
            new RealName('ソン・チェヨン'),
            [
                new GroupIdentifier(StrTestHelper::generateUlid()),
            ],
            new Birthday(new DateTimeImmutable('1999-04-23')),
            new Career('TWICEメンバー'),
            new ImagePath('/images/chaeyoung_ja.webp'),
            new RelevantVideoLinks([
                new ExternalContentLink('https://example.youtube.com/watch?v=test'),
            ]),
            ApprovalStatus::Approved,
        );

        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $memberRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$selfMember]);

        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $memberService = $this->app->make(MemberServiceInterface::class);

        $result = $memberService->existsApprovedButNotTranslatedMember(
            $translationSetIdentifier,
            $excludeMemberIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * 正常系: DraftMemberが存在しない場合、falseを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedMemberWhenNoDrafts(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $excludeMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());

        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $memberRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([]);

        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $memberService = $this->app->make(MemberServiceInterface::class);

        $result = $memberService->existsApprovedButNotTranslatedMember(
            $translationSetIdentifier,
            $excludeMemberIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * 正常系: 複数のApproved状態のDraftMemberが存在する場合、trueを返すこと
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testExistsApprovedButNotTranslatedMemberWhenMultipleApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $excludeMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());

        // 韓国語版 (Approved)
        $koreanMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $koreanMember = new DraftMember(
            $koreanMemberIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::KOREAN,
            new MemberName('채영'),
            new RealName('손채영'),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Approved,
        );

        // 英語版 (Approved)
        $englishMemberIdentifier = new MemberIdentifier(StrTestHelper::generateUlid());
        $englishMember = new DraftMember(
            $englishMemberIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::ENGLISH,
            new MemberName('Chaeyoung'),
            new RealName('Son Chaeyoung'),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Approved,
        );

        // 日本語版 (Pending, 除外対象)
        $japaneseMember = new DraftMember(
            $excludeMemberIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::JAPANESE,
            new MemberName('チェヨン'),
            new RealName('ソン・チェヨン'),
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Pending,
        );

        $memberRepository = Mockery::mock(MemberRepositoryInterface::class);
        $memberRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$koreanMember, $englishMember, $japaneseMember]);

        $this->app->instance(MemberRepositoryInterface::class, $memberRepository);
        $memberService = $this->app->make(MemberServiceInterface::class);

        $result = $memberService->existsApprovedButNotTranslatedMember(
            $translationSetIdentifier,
            $excludeMemberIdentifier,
        );

        $this->assertTrue($result);
    }
}
