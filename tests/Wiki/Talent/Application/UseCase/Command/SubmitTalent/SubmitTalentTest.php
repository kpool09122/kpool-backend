<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\SubmitTalent;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent\SubmitTalent;
use Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent\SubmitTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent\SubmitTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\TalentHistory;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentHistoryRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitTalentTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        // TODO: 各実装クラス作ったら削除する
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $this->assertInstanceOf(SubmitTalent::class, $submitTalent);
    }

    /**
     * 正常系：正しく下書きステータスが変更されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $submitTalentInfo = $this->createSubmitTalentInfo(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitTalentInput(
            $submitTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($submitTalentInfo->draftTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($submitTalentInfo->talentIdentifier)
            ->andReturn($submitTalentInfo->draftTalent);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($submitTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($submitTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);
        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $talent = $submitTalent->process($input);
        $this->assertNotSame($submitTalentInfo->status, $talent->status());
        $this->assertSame(ApprovalStatus::UnderReview, $talent->status());
    }

    /**
     * 異常系：指定したIDに紐づくTalentが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundTalent(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new SubmitTalentInput(
            $talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn(null);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(TalentNotFoundException::class);
        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $submitTalent->process($input);
    }

    /**
     * 異常系：承認ステータスがPendingかRejected以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testInvalidStatus(): void
    {
        $submitTalentInfo = $this->createSubmitTalentInfo(status: ApprovalStatus::Approved);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], []);

        $input = new SubmitTalentInput(
            $submitTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($submitTalentInfo->talentIdentifier)
            ->andReturn($submitTalentInfo->draftTalent);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(InvalidStatusException::class);
        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $submitTalent->process($input);
    }

    /**
     * 正常系：COLLABORATORがTalentを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], []);

        $submitTalentInfo = $this->createSubmitTalentInfo(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitTalentInput(
            $submitTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($submitTalentInfo->talentIdentifier)
            ->andReturn($submitTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($submitTalentInfo->draftTalent)
            ->andReturn(null);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($submitTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($submitTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $result = $submitTalent->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：AGENCY_ACTORがTalentを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithAgencyActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], []);

        $submitTalentInfo = $this->createSubmitTalentInfo(
            agencyId: $agencyId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitTalentInput(
            $submitTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($submitTalentInfo->talentIdentifier)
            ->andReturn($submitTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($submitTalentInfo->draftTalent)
            ->andReturn(null);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($submitTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($submitTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $result = $submitTalent->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：GROUP_ACTORがTalentを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithGroupActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [StrTestHelper::generateUlid(), StrTestHelper::generateUlid()];
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, $agencyId, $groupIds, []);

        $submitTalentInfo = $this->createSubmitTalentInfo(
            agencyId: $agencyId,
            groupIds: $groupIds,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitTalentInput(
            $submitTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($submitTalentInfo->talentIdentifier)
            ->andReturn($submitTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($submitTalentInfo->draftTalent)
            ->andReturn(null);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($submitTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($submitTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $result = $submitTalent->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：TALENT_ACTORがTalentを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithTalentActor(): void
    {
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [StrTestHelper::generateUlid(), StrTestHelper::generateUlid()];
        $talentId = StrTestHelper::generateUlid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, $agencyId, $groupIds, [$talentId]);

        $submitTalentInfo = $this->createSubmitTalentInfo(
            agencyId: $agencyId,
            groupIds: $groupIds,
            talentId: $talentId,
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitTalentInput(
            $submitTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($submitTalentInfo->talentIdentifier)
            ->andReturn($submitTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($submitTalentInfo->draftTalent)
            ->andReturn(null);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($submitTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($submitTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $result = $submitTalent->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：SENIOR_COLLABORATORがTalentを提出できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], []);

        $submitTalentInfo = $this->createSubmitTalentInfo(
            operatorIdentifier: new EditorIdentifier((string) $principalIdentifier),
        );

        $input = new SubmitTalentInput(
            $submitTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($submitTalentInfo->talentIdentifier)
            ->andReturn($submitTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($submitTalentInfo->draftTalent)
            ->andReturn(null);

        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);
        $talentHistoryFactory->shouldReceive('create')
            ->once()
            ->andReturn($submitTalentInfo->history);
        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryRepository->shouldReceive('save')
            ->once()
            ->with($submitTalentInfo->history)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $result = $submitTalent->process($input);

        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 異常系：NONEロールがTalentを提出しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithNoneRole(): void
    {
        $submitTalentInfo = $this->createSubmitTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], []);

        $input = new SubmitTalentInput(
            $submitTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($submitTalentInfo->talentIdentifier)
            ->andReturn($submitTalentInfo->draftTalent);

        $talentHistoryRepository = Mockery::mock(TalentHistoryRepositoryInterface::class);
        $talentHistoryFactory = Mockery::mock(TalentHistoryFactoryInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentHistoryRepositoryInterface::class, $talentHistoryRepository);
        $this->app->instance(TalentHistoryFactoryInterface::class, $talentHistoryFactory);

        $this->expectException(UnauthorizedException::class);
        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $submitTalent->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $agencyId
     * @param array<string>|null $groupIds
     * @param string|null $talentId
     * @param ApprovalStatus $status
     * @param EditorIdentifier|null $operatorIdentifier
     * @return SubmitTalentTestData
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function createSubmitTalentInfo(
        ?string $agencyId = null,
        ?array $groupIds = null,
        ?string $talentId = null,
        ApprovalStatus $status = ApprovalStatus::Pending,
        ?EditorIdentifier $operatorIdentifier = null,
    ): SubmitTalentTestData {
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $agencyIdentifier = new AgencyIdentifier($agencyId ?? StrTestHelper::generateUlid());
        $groupIdentifiers = $groupIds !== null
            ? array_map(static fn ($id) => new GroupIdentifier($id), $groupIds)
            : [
                new GroupIdentifier(StrTestHelper::generateUlid()),
                new GroupIdentifier(StrTestHelper::generateUlid()),
            ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $imageLink = new ImagePath('/resources/public/images/before.webp');

        $talentIdentifier = new TalentIdentifier($talentId ?? StrTestHelper::generateUlid());
        $talent = new DraftTalent(
            $talentIdentifier,
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
            $imageLink,
            $relevantVideoLinks,
            $status,
        );

        $historyIdentifier = new TalentHistoryIdentifier(StrTestHelper::generateUlid());
        $history = new TalentHistory(
            $historyIdentifier,
            $operatorIdentifier ?? new EditorIdentifier(StrTestHelper::generateUlid()),
            $talent->editorIdentifier(),
            $talent->publishedTalentIdentifier(),
            $talent->talentIdentifier(),
            $status,
            ApprovalStatus::UnderReview,
            $talent->name(),
            new DateTimeImmutable('now'),
        );

        return new SubmitTalentTestData(
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
            $base64EncodedImage,
            $link1,
            $link2,
            $link3,
            $relevantVideoLinks,
            $imageLink,
            $talentIdentifier,
            $status,
            $talent,
            $historyIdentifier,
            $history,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class SubmitTalentTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param GroupIdentifier[] $groupIdentifiers
     */
    public function __construct(
        public TalentIdentifier         $publishedTalentIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public EditorIdentifier         $editorIdentifier,
        public Language                 $language,
        public TalentName               $name,
        public RealName                 $realName,
        public AgencyIdentifier         $agencyIdentifier,
        public array                    $groupIdentifiers,
        public Birthday                 $birthday,
        public Career                   $career,
        public string                   $base64EncodedImage,
        public ExternalContentLink      $link1,
        public ExternalContentLink      $link2,
        public ExternalContentLink      $link3,
        public RelevantVideoLinks $relevantVideoLinks,
        public ImagePath $imageLink,
        public TalentIdentifier $talentIdentifier,
        public ApprovalStatus $status,
        public DraftTalent $draftTalent,
        public TalentHistoryIdentifier $historyIdentifier,
        public TalentHistory $history,
    ) {
    }
}
