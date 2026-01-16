<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\MergeTalent;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\UseCase\Command\MergeTalent\MergeTalent;
use Source\Wiki\Talent\Application\UseCase\Command\MergeTalent\MergeTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\MergeTalent\MergeTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MergeTalentTest extends TestCase
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
        $mergeTalent = $this->app->make(MergeTalentInterface::class);
        $this->assertInstanceOf(MergeTalent::class, $mergeTalent);
    }

    /**
     * 正常系：正しくTalent Entityがマージされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $dummyTalent = $this->createDummyMergeTalent();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new MergeTalentInput(
            $dummyTalent->talentIdentifier,
            $dummyTalent->name,
            $dummyTalent->realName,
            $dummyTalent->agencyIdentifier,
            $dummyTalent->groupIdentifiers,
            $dummyTalent->birthday,
            $dummyTalent->career,
            $dummyTalent->relevantVideoLinks,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($dummyTalent->talent)
            ->andReturn(null);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTalent->talentIdentifier)
            ->andReturn($dummyTalent->talent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $mergeTalent = $this->app->make(MergeTalentInterface::class);
        $talent = $mergeTalent->process($input);
        $this->assertSame((string)$dummyTalent->talentIdentifier, (string)$talent->talentIdentifier());
        $this->assertSame((string)$dummyTalent->publishedTalentIdentifier, (string)$talent->publishedTalentIdentifier());
        $this->assertSame($dummyTalent->language->value, $talent->language()->value);
        $this->assertSame((string)$dummyTalent->name, (string)$talent->name());
        $this->assertSame((string)$dummyTalent->realName, (string)$talent->realName());
        $this->assertSame((string)$dummyTalent->agencyIdentifier, (string)$talent->agencyIdentifier());
        $this->assertSame($dummyTalent->groupIdentifiers, $talent->groupIdentifiers());
        $this->assertSame($dummyTalent->birthday, $talent->birthday());
        $this->assertSame((string)$dummyTalent->career, (string)$talent->career());
        $this->assertSame($principalIdentifier, $talent->mergerIdentifier());
        $this->assertSame($mergedAt, $talent->mergedAt());
    }

    /**
     * 異常系：指定したIDに紐づくTalentが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testWhenNotFoundTalent(): void
    {
        $dummyTalent = $this->createDummyMergeTalent();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new MergeTalentInput(
            $dummyTalent->talentIdentifier,
            $dummyTalent->name,
            $dummyTalent->realName,
            $dummyTalent->agencyIdentifier,
            $dummyTalent->groupIdentifiers,
            $dummyTalent->birthday,
            $dummyTalent->career,
            $dummyTalent->relevantVideoLinks,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTalent->talentIdentifier)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->expectException(TalentNotFoundException::class);
        $mergeTalent = $this->app->make(MergeTalentInterface::class);
        $mergeTalent->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyTalent = $this->createDummyMergeTalent();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new MergeTalentInput(
            $dummyTalent->talentIdentifier,
            $dummyTalent->name,
            $dummyTalent->realName,
            $dummyTalent->agencyIdentifier,
            $dummyTalent->groupIdentifiers,
            $dummyTalent->birthday,
            $dummyTalent->career,
            $dummyTalent->relevantVideoLinks,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTalent->talentIdentifier)
            ->andReturn($dummyTalent->talent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $this->expectException(PrincipalNotFoundException::class);
        $mergeTalent = $this->app->make(MergeTalentInterface::class);
        $mergeTalent->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORがTalentをマージできること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithAgencyActor(): void
    {
        $dummyTalent = $this->createDummyMergeTalent();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $agencyId = (string) $dummyTalent->agencyIdentifier;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [], []);

        $input = new MergeTalentInput(
            $dummyTalent->talentIdentifier,
            $dummyTalent->name,
            $dummyTalent->realName,
            $dummyTalent->agencyIdentifier,
            $dummyTalent->groupIdentifiers,
            $dummyTalent->birthday,
            $dummyTalent->career,
            $dummyTalent->relevantVideoLinks,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTalent->talentIdentifier)
            ->andReturn($dummyTalent->talent);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($dummyTalent->talent)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $mergeTalent = $this->app->make(MergeTalentInterface::class);
        $mergeTalent->process($input);
    }

    /**
     * 正常系：TALENT_ACTORがTalentをマージできること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithTalentActor(): void
    {
        $dummyTalent = $this->createDummyMergeTalent();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $groupIds = array_map(static fn ($g) => (string) $g, $dummyTalent->groupIdentifiers);
        $talentId = (string) $dummyTalent->talentIdentifier;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, $groupIds, [$talentId]);

        $input = new MergeTalentInput(
            $dummyTalent->talentIdentifier,
            $dummyTalent->name,
            $dummyTalent->realName,
            $dummyTalent->agencyIdentifier,
            $dummyTalent->groupIdentifiers,
            $dummyTalent->birthday,
            $dummyTalent->career,
            $dummyTalent->relevantVideoLinks,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTalent->talentIdentifier)
            ->andReturn($dummyTalent->talent);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($dummyTalent->talent)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $mergeTalent = $this->app->make(MergeTalentInterface::class);
        $mergeTalent->process($input);
    }

    /**
     * 異常系：COLLABORATORがTalentをマージしようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws PrincipalNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithCollaborator(): void
    {
        $dummyTalent = $this->createDummyMergeTalent();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new MergeTalentInput(
            $dummyTalent->talentIdentifier,
            $dummyTalent->name,
            $dummyTalent->realName,
            $dummyTalent->agencyIdentifier,
            $dummyTalent->groupIdentifiers,
            $dummyTalent->birthday,
            $dummyTalent->career,
            $dummyTalent->relevantVideoLinks,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTalent->talentIdentifier)
            ->andReturn($dummyTalent->talent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $mergeTalent = $this->app->make(MergeTalentInterface::class);
        $mergeTalent->process($input);
    }

    /**
     * 異常系：NONEロールがTalentをマージしようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws PrincipalNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummyTalent = $this->createDummyMergeTalent();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new MergeTalentInput(
            $dummyTalent->talentIdentifier,
            $dummyTalent->name,
            $dummyTalent->realName,
            $dummyTalent->agencyIdentifier,
            $dummyTalent->groupIdentifiers,
            $dummyTalent->birthday,
            $dummyTalent->career,
            $dummyTalent->relevantVideoLinks,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTalent->talentIdentifier)
            ->andReturn($dummyTalent->talent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $mergeTalent = $this->app->make(MergeTalentInterface::class);
        $mergeTalent->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがTalentをマージできること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $dummyTalent = $this->createDummyMergeTalent();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new MergeTalentInput(
            $dummyTalent->talentIdentifier,
            $dummyTalent->name,
            $dummyTalent->realName,
            $dummyTalent->agencyIdentifier,
            $dummyTalent->groupIdentifiers,
            $dummyTalent->birthday,
            $dummyTalent->career,
            $dummyTalent->relevantVideoLinks,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('findById')
            ->once()
            ->with($dummyTalent->talentIdentifier)
            ->andReturn($dummyTalent->talent);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($dummyTalent->talent)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $mergeTalent = $this->app->make(MergeTalentInterface::class);
        $mergeTalent->process($input);
    }

    /**
     * @return MergeTalentTestData
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function createDummyMergeTalent(): MergeTalentTestData
    {
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다.');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);
        $imagePath = new ImagePath('/resources/public/images/test.webp');

        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            '',
            $realName,
            '',
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $imagePath,
            $relevantVideoLinks,
            $status,
        );

        return new MergeTalentTestData(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $relevantVideoLinks,
            $talentIdentifier,
            $status,
            $talent,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class MergeTalentTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param GroupIdentifier[] $groupIdentifiers
     */
    public function __construct(
        public TalentIdentifier         $publishedTalentIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public TalentName               $name,
        public RealName                 $realName,
        public AgencyIdentifier         $agencyIdentifier,
        public array                    $groupIdentifiers,
        public Birthday                 $birthday,
        public Career                   $career,
        public RelevantVideoLinks       $relevantVideoLinks,
        public TalentIdentifier         $talentIdentifier,
        public ApprovalStatus           $status,
        public DraftTalent              $talent,
    ) {
    }
}
