<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\RejectTalent;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\UseCase\Command\RejectTalent\RejectTalent;
use Source\Wiki\Talent\Application\UseCase\Command\RejectTalent\RejectTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\RejectTalent\RejectTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\TalentServiceInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectTalentTest extends TestCase
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
        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $this->assertInstanceOf(RejectTalent::class, $rejectTalent);
    }

    /**
     * 正常系：正しく下書きが拒否されること.
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
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($rejectTalentInfo->draftTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $talent = $rejectTalent->process($input);
        $this->assertNotSame($rejectTalentInfo->status, $talent->status());
        $this->assertSame(ApprovalStatus::Rejected, $talent->status());
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
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new RejectTalentInput(
            $talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn(null);

        $talentService = Mockery::mock(TalentServiceInterface::class);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(TalentServiceInterface::class, $talentService);

        $this->expectException(TalentNotFoundException::class);
        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $rejectTalent->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testInvalidStatus(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $status = ApprovalStatus::Approved;
        $talent = new DraftTalent(
            $rejectTalentInfo->talentIdentifier,
            $rejectTalentInfo->publishedTalentIdentifier,
            $rejectTalentInfo->translationSetIdentifier,
            $rejectTalentInfo->editorIdentifier,
            $rejectTalentInfo->translation,
            $rejectTalentInfo->name,
            $rejectTalentInfo->realName,
            $rejectTalentInfo->agencyIdentifier,
            $rejectTalentInfo->groupIdentifiers,
            $rejectTalentInfo->birthday,
            $rejectTalentInfo->career,
            $rejectTalentInfo->imageLink,
            $rejectTalentInfo->relevantVideoLinks,
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($talent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $this->expectException(InvalidStatusException::class);
        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $rejectTalent->process($input);
    }

    /**
     * 異常系：拒否権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testUnauthorizedRole(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $rejectTalent->process($input);
    }

    /**
     * 正常系：ADMINISTRATORがメンバーを拒否できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testProcessWithAdministrator(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($rejectTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $result = $rejectTalent->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：AGENCY_ACTORが自分の所属していないグループのメンバーを拒否しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testUnauthorizedAgencyScope(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherAgencyId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $anotherAgencyId, [], null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $rejectTalent->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORが自分の所属するグループのメンバーを拒否できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $rejectTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $rejectTalentInfo->groupIdentifiers);
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, $groupIds, null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($rejectTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $result = $rejectTalent->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループのメンバーを拒否しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $rejectTalentInfo->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, $agencyId, [$anotherGroupId], null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $rejectTalent->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループのメンバーを拒否できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testAuthorizedGroupActor(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $rejectTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $rejectTalentInfo->groupIdentifiers);
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, $agencyId, $groupIds, null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($rejectTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $result = $rejectTalent->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：MEMBER_ACTORが自分の所属していないグループのメンバーを拒否しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $rejectTalentInfo->agencyIdentifier;
        $anotherGroupId = StrTestHelper::generateUlid();
        $talentId = (string) $rejectTalentInfo->talentIdentifier;
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, $agencyId, [$anotherGroupId], $talentId);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $rejectTalent->process($input);
    }

    /**
     * 正常系：MEMBER_ACTORが自分の所属するグループのメンバーを拒否できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testAuthorizedTalentActor(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $agencyId = (string) $rejectTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $rejectTalentInfo->groupIdentifiers);
        $talentId = (string) $rejectTalentInfo->talentIdentifier;
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, $agencyId, $groupIds, $talentId);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($rejectTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $result = $rejectTalent->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 正常系：SENIOR_COLLABORATORがメンバーを却下できること.
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
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::SENIOR_COLLABORATOR, null, [], null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($rejectTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $result = $rejectTalent->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：NONEロールがメンバーを却下しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testUnauthorizedNoneRole(): void
    {
        $rejectTalentInfo = $this->createRejectTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::NONE, null, [], null);

        $input = new RejectTalentInput(
            $rejectTalentInfo->talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($rejectTalentInfo->talentIdentifier)
            ->andReturn($rejectTalentInfo->draftTalent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $this->expectException(UnauthorizedException::class);
        $rejectTalent = $this->app->make(RejectTalentInterface::class);
        $rejectTalent->process($input);
    }

    /**
     * @return RejectTalentTestData
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function createRejectTalentInfo(): RejectTalentTestData
    {
        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $groupIdentifiers = [
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

        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::UnderReview;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
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

        return new RejectTalentTestData(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
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
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class RejectTalentTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     * @param GroupIdentifier[] $groupIdentifiers
     */
    public function __construct(
        public TalentIdentifier $publishedTalentIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public EditorIdentifier $editorIdentifier,
        public Translation $translation,
        public TalentName $name,
        public RealName $realName,
        public AgencyIdentifier $agencyIdentifier,
        public array $groupIdentifiers,
        public Birthday $birthday,
        public Career $career,
        public string $base64EncodedImage,
        public ExternalContentLink $link1,
        public ExternalContentLink $link2,
        public ExternalContentLink $link3,
        public RelevantVideoLinks $relevantVideoLinks,
        public ImagePath $imageLink,
        public TalentIdentifier $talentIdentifier,
        public ApprovalStatus $status,
        public DraftTalent $draftTalent,
    ) {
    }
}
