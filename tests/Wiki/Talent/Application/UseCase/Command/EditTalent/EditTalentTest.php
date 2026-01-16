<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\EditTalent;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\ImageServiceInterface;
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
use Source\Wiki\Talent\Application\UseCase\Command\EditTalent\EditTalent;
use Source\Wiki\Talent\Application\UseCase\Command\EditTalent\EditTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\EditTalent\EditTalentInterface;
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

class EditTalentTest extends TestCase
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
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $talentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $this->app->instance(DraftTalentRepositoryInterface::class, $talentRepository);
        $editTalent = $this->app->make(EditTalentInterface::class);
        $this->assertInstanceOf(EditTalent::class, $editTalent);
    }

    /**
     * 正常系：正しくTalent Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $editTalentInfo = $this->createEditTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new EditTalentInput(
            $editTalentInfo->talentIdentifier,
            $editTalentInfo->name,
            $editTalentInfo->realName,
            $editTalentInfo->agencyIdentifier,
            $editTalentInfo->groupIdentifiers,
            $editTalentInfo->birthday,
            $editTalentInfo->career,
            $editTalentInfo->base64EncodedImage,
            $editTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($editTalentInfo->base64EncodedImage)
            ->andReturn($editTalentInfo->imageLink);

        $talentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($editTalentInfo->draftTalent)
            ->andReturn(null);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($editTalentInfo->talentIdentifier)
            ->andReturn($editTalentInfo->draftTalent);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $talentRepository);
        $editTalent = $this->app->make(EditTalentInterface::class);
        $talent = $editTalent->process($input);
        $this->assertSame((string)$editTalentInfo->talentIdentifier, (string)$talent->talentIdentifier());
        $this->assertSame((string)$editTalentInfo->publishedTalentIdentifier, (string)$talent->publishedTalentIdentifier());
        $this->assertSame((string)$editTalentInfo->editorIdentifier, (string)$talent->editorIdentifier());
        $this->assertSame($editTalentInfo->language->value, $talent->language()->value);
        $this->assertSame((string)$editTalentInfo->name, (string)$talent->name());
        $this->assertSame((string)$editTalentInfo->realName, (string)$talent->realName());
        $this->assertSame((string)$editTalentInfo->agencyIdentifier, (string)$talent->agencyIdentifier());
        $this->assertSame($editTalentInfo->groupIdentifiers, $talent->groupIdentifiers());
        $this->assertSame($editTalentInfo->birthday, $talent->birthday());
        $this->assertSame((string)$editTalentInfo->career, (string)$talent->career());
        $this->assertSame((string)$editTalentInfo->imageLink, (string)$talent->imageLink());
        $this->assertSame($editTalentInfo->relevantVideoLinks->toStringArray(), $talent->relevantVideoLinks()->toStringArray());
        $this->assertSame($editTalentInfo->status, $talent->status());
    }

    /**
     * 異常系：IDに紐づくメンバーが存在しない場合、例外がthrowされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundTalent(): void
    {
        $editTalentInfo = $this->createEditTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new EditTalentInput(
            $editTalentInfo->talentIdentifier,
            $editTalentInfo->name,
            $editTalentInfo->realName,
            $editTalentInfo->agencyIdentifier,
            $editTalentInfo->groupIdentifiers,
            $editTalentInfo->birthday,
            $editTalentInfo->career,
            $editTalentInfo->base64EncodedImage,
            $editTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $talentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($editTalentInfo->talentIdentifier)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $talentRepository);

        $this->expectException(TalentNotFoundException::class);
        $editTalent = $this->app->make(EditTalentInterface::class);
        $editTalent->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $editTalentInfo = $this->createEditTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new EditTalentInput(
            $editTalentInfo->talentIdentifier,
            $editTalentInfo->name,
            $editTalentInfo->realName,
            $editTalentInfo->agencyIdentifier,
            $editTalentInfo->groupIdentifiers,
            $editTalentInfo->birthday,
            $editTalentInfo->career,
            $editTalentInfo->base64EncodedImage,
            $editTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $talentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($editTalentInfo->talentIdentifier)
            ->andReturn($editTalentInfo->draftTalent);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $talentRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $editTalent = $this->app->make(EditTalentInterface::class);
        $editTalent->process($input);
    }

    /**
     * 正常系：COLLABORATORがメンバーを編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $editTalentInfo = $this->createEditTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new EditTalentInput(
            $editTalentInfo->talentIdentifier,
            $editTalentInfo->name,
            $editTalentInfo->realName,
            $editTalentInfo->agencyIdentifier,
            $editTalentInfo->groupIdentifiers,
            $editTalentInfo->birthday,
            $editTalentInfo->career,
            $editTalentInfo->base64EncodedImage,
            $editTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($editTalentInfo->talentIdentifier)
            ->andReturn($editTalentInfo->draftTalent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($editTalentInfo->draftTalent)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($editTalentInfo->base64EncodedImage)
            ->andReturn($editTalentInfo->imageLink);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $talentRepository);

        $useCase = $this->app->make(EditTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORがメンバーを編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $editTalentInfo = $this->createEditTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string)$editTalentInfo->agencyIdentifier;
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, [], []);

        $input = new EditTalentInput(
            $editTalentInfo->talentIdentifier,
            $editTalentInfo->name,
            $editTalentInfo->realName,
            $editTalentInfo->agencyIdentifier,
            $editTalentInfo->groupIdentifiers,
            $editTalentInfo->birthday,
            $editTalentInfo->career,
            $editTalentInfo->base64EncodedImage,
            $editTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($editTalentInfo->talentIdentifier)
            ->andReturn($editTalentInfo->draftTalent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($editTalentInfo->draftTalent)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($editTalentInfo->base64EncodedImage)
            ->andReturn($editTalentInfo->imageLink);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $talentRepository);

        $useCase = $this->app->make(EditTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：MEMBER_ACTORがメンバーを編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithTalentActor(): void
    {
        $editTalentInfo = $this->createEditTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string)$editTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $editTalentInfo->groupIdentifiers);
        $talentId = (string)$editTalentInfo->talentIdentifier;
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $agencyId, $groupIds, [$talentId]);

        $input = new EditTalentInput(
            $editTalentInfo->talentIdentifier,
            $editTalentInfo->name,
            $editTalentInfo->realName,
            $editTalentInfo->agencyIdentifier,
            $editTalentInfo->groupIdentifiers,
            $editTalentInfo->birthday,
            $editTalentInfo->career,
            $editTalentInfo->base64EncodedImage,
            $editTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($editTalentInfo->talentIdentifier)
            ->andReturn($editTalentInfo->draftTalent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($editTalentInfo->draftTalent)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($editTalentInfo->base64EncodedImage)
            ->andReturn($editTalentInfo->imageLink);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $talentRepository);

        $useCase = $this->app->make(EditTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがメンバーを編集できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $editTalentInfo = $this->createEditTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new EditTalentInput(
            $editTalentInfo->talentIdentifier,
            $editTalentInfo->name,
            $editTalentInfo->realName,
            $editTalentInfo->agencyIdentifier,
            $editTalentInfo->groupIdentifiers,
            $editTalentInfo->birthday,
            $editTalentInfo->career,
            $editTalentInfo->base64EncodedImage,
            $editTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($editTalentInfo->talentIdentifier)
            ->andReturn($editTalentInfo->draftTalent);
        $talentRepository->shouldReceive('save')
            ->once()
            ->with($editTalentInfo->draftTalent)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($editTalentInfo->base64EncodedImage)
            ->andReturn($editTalentInfo->imageLink);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $talentRepository);

        $useCase = $this->app->make(EditTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * 異常系：NONEロールがメンバーを編集しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $editTalentInfo = $this->createEditTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new EditTalentInput(
            $editTalentInfo->talentIdentifier,
            $editTalentInfo->name,
            $editTalentInfo->realName,
            $editTalentInfo->agencyIdentifier,
            $editTalentInfo->groupIdentifiers,
            $editTalentInfo->birthday,
            $editTalentInfo->career,
            $editTalentInfo->base64EncodedImage,
            $editTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $talentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($editTalentInfo->talentIdentifier)
            ->andReturn($editTalentInfo->draftTalent);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentRepositoryInterface::class, $talentRepository);

        $this->setPolicyEvaluatorResult(false);

        $this->expectException(UnauthorizedException::class);
        $useCase = $this->app->make(EditTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * @return EditTalentTestData
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function createEditTalentInfo(): EditTalentTestData
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
            $imageLink,
            $relevantVideoLinks,
            $status,
        );

        return new EditTalentTestData(
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
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class EditTalentTestData
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
        public string                   $base64EncodedImage,
        public ExternalContentLink      $link1,
        public ExternalContentLink      $link2,
        public ExternalContentLink      $link3,
        public RelevantVideoLinks $relevantVideoLinks,
        public ImagePath $imageLink,
        public TalentIdentifier $talentIdentifier,
        public ApprovalStatus $status,
        public DraftTalent $draftTalent,
    ) {
    }
}
