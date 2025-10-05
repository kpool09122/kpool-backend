<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\SubmitTalent;

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
use Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent\SubmitTalent;
use Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent\SubmitTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent\SubmitTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
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
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imageLink = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new SubmitTalentInput(
            $talentIdentifier,
            $principal,
        );

        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($talent)
            ->andReturn(null);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $talent = $submitTalent->process($input);
        $this->assertNotSame($status, $talent->status());
        $this->assertSame(ApprovalStatus::UnderReview, $talent->status());
    }

    /**
     * 異常系：指定したIDに紐づくGroupが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundAgency(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new SubmitTalentInput(
            $talentIdentifier,
            $principal,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

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
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $realName = new RealName('손채영');
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];
        $birthday = new Birthday(new DateTimeImmutable('1994-01-01'));
        $career = new Career('### **경력 소개 예시**
대학교 졸업 후, 주식회사 〇〇에 영업직으로 입사하여 법인 대상 IT 솔루션의 신규 고객 개척 및 기존 고객 관리에 4년간 종사했습니다. 고객의 잠재적인 과제를 깊이 있게 파악하고 해결책을 제안하는 \'과제 해결형 영업\'을 강점으로 삼고 있으며, 입사 3년 차에는 연간 개인 매출 목표의 120%를 달성하여 사내 영업 MVP를 수상했습니다.
2021년부터는 사업 회사의 마케팅부로 이직하여 자사 제품의 프로모션 전략 입안부터 실행까지 담당하고 있습니다. 특히 디지털 마케팅 영역에 주력하여 웹 광고 운영, SEO 대책, SNS 콘텐츠 기획 등을 통해 잠재 고객 확보 수를 전년 대비 150% 향상시킨 실적이 있습니다. 또한, 데이터 분석에 기반한 시책 개선을 특기로 하고 있으며, Google Analytics 등을 활용하여 효과 측정과 다음 전략 수립으로 연결해 왔습니다.
지금까지의 경력을 통해 쌓아온 \'고객의 과제를 정확하게 파악하는 능력\'과 \'데이터를 기반으로 전략을 세우고 실행하는 능력\'을 활용하여 귀사의 사업 성장에 기여하고 싶습니다. 앞으로는 영업과 마케팅 양쪽의 시각을 겸비한 강점을 살려 보다 효과적인 고객 접근을 실현할 수 있다고 확신합니다.');
        $imageLink = new ImagePath('/resources/public/images/before.webp');
        $link1 = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $link2 = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $link3 = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');
        $externalContentLinks = [$link1, $link2, $link3];
        $relevantVideoLinks = new RelevantVideoLinks($externalContentLinks);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new SubmitTalentInput(
            $talentIdentifier,
            $principal,
        );

        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Approved;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editorIdentifier,
            $translation,
            $name,
            $realName,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

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
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);

        $input = new SubmitTalentInput(
            $talentIdentifier,
            $principal,
        );

        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editorIdentifier,
            Translation::KOREAN,
            new TalentName('채영'),
            new RealName('손채영'),
            $groupIdentifiers,
            new Birthday(new DateTimeImmutable('1994-01-01')),
            new Career('career'),
            new ImagePath('/resources/public/images/before.webp'),
            new RelevantVideoLinks([]),
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $result = $submitTalent->process($input);

        $this->assertInstanceOf(DraftTalent::class, $result);
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
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $agencyId = StrTestHelper::generateUlid();
        $groupIdentifiers = [
            new GroupIdentifier(StrTestHelper::generateUlid()),
        ];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::AGENCY_ACTOR, $agencyId, [], null);

        $input = new SubmitTalentInput(
            $talentIdentifier,
            $principal,
        );

        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editorIdentifier,
            Translation::KOREAN,
            new TalentName('채영'),
            new RealName('손채영'),
            $groupIdentifiers,
            new Birthday(new DateTimeImmutable('1994-01-01')),
            new Career('career'),
            new ImagePath('/resources/public/images/before.webp'),
            new RelevantVideoLinks([]),
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $result = $submitTalent->process($input);

        $this->assertInstanceOf(DraftTalent::class, $result);
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
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $groupIdentifiers = [
            new GroupIdentifier($groupId),
        ];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$groupId], null);

        $input = new SubmitTalentInput(
            $talentIdentifier,
            $principal,
        );

        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editorIdentifier,
            Translation::KOREAN,
            new TalentName('채영'),
            new RealName('손채영'),
            $groupIdentifiers,
            new Birthday(new DateTimeImmutable('1994-01-01')),
            new Career('career'),
            new ImagePath('/resources/public/images/before.webp'),
            new RelevantVideoLinks([]),
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $result = $submitTalent->process($input);

        $this->assertInstanceOf(DraftTalent::class, $result);
        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }

    /**
     * 正常系：MEMBER_ACTORがTalentを提出できること.
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
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $groupId = StrTestHelper::generateUlid();
        $groupIdentifiers = [
            new GroupIdentifier($groupId),
        ];
        $talentId = StrTestHelper::generateUlid();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$groupId], $talentId);

        $input = new SubmitTalentInput(
            $talentIdentifier,
            $principal,
        );

        $publishedTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $status = ApprovalStatus::Pending;
        $talent = new DraftTalent(
            $talentIdentifier,
            $publishedTalentIdentifier,
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            $editorIdentifier,
            Translation::KOREAN,
            new TalentName('채영'),
            new RealName('손채영'),
            $groupIdentifiers,
            new Birthday(new DateTimeImmutable('1994-01-01')),
            new Career('career'),
            new ImagePath('/resources/public/images/before.webp'),
            new RelevantVideoLinks([]),
            $status,
        );

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findDraftById')
            ->once()
            ->with($talentIdentifier)
            ->andReturn($talent);
        $talentRepository->shouldReceive('saveDraft')
            ->once()
            ->with($talent)
            ->andReturn(null);

        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);

        $submitTalent = $this->app->make(SubmitTalentInterface::class);
        $result = $submitTalent->process($input);

        $this->assertInstanceOf(DraftTalent::class, $result);
        $this->assertSame(ApprovalStatus::UnderReview, $result->status());
    }
}
