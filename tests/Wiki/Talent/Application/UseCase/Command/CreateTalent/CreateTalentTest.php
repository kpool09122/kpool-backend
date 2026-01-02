<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\CreateTalent;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Application\UseCase\Command\CreateTalent\CreateTalent;
use Source\Wiki\Talent\Application\UseCase\Command\CreateTalent\CreateTalentInput;
use Source\Wiki\Talent\Application\UseCase\Command\CreateTalent\CreateTalentInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateTalentTest extends TestCase
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
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $createTalent = $this->app->make(CreateTalentInterface::class);
        $this->assertInstanceOf(CreateTalent::class, $createTalent);
    }

    /**
     * 正常系：正しくTalent Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $createTalentInfo = $this->createCreateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new CreateTalentInput(
            $createTalentInfo->publishedTalentIdentifier,
            $createTalentInfo->language,
            $createTalentInfo->name,
            $createTalentInfo->realName,
            $createTalentInfo->agencyIdentifier,
            $createTalentInfo->groupIdentifiers,
            $createTalentInfo->birthday,
            $createTalentInfo->career,
            $createTalentInfo->base64EncodedImage,
            $createTalentInfo->relevantVideoLinks,
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
            ->with($createTalentInfo->base64EncodedImage)
            ->andReturn($createTalentInfo->imageLink);

        $talentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createTalentInfo->language, $createTalentInfo->name)
            ->andReturn($createTalentInfo->draftTalent);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($createTalentInfo->publishedTalentIdentifier)
            ->andReturn($createTalentInfo->publishedTalent);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($createTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);
        $createTalent = $this->app->make(CreateTalentInterface::class);
        $talent = $createTalent->process($input);
        $this->assertTrue(UuidValidator::isValid((string)$talent->talentIdentifier()));
        $this->assertSame((string)$createTalentInfo->publishedTalentIdentifier, (string)$talent->publishedTalentIdentifier());
        $this->assertSame((string)$createTalentInfo->editorIdentifier, (string)$talent->editorIdentifier());
        $this->assertSame($createTalentInfo->language->value, $talent->language()->value);
        $this->assertSame((string)$createTalentInfo->name, (string)$talent->name());
        $this->assertSame((string)$createTalentInfo->realName, (string)$talent->realName());
        $this->assertSame($createTalentInfo->groupIdentifiers, $talent->groupIdentifiers());
        $this->assertSame($createTalentInfo->birthday, $talent->birthday());
        $this->assertSame((string)$createTalentInfo->career, (string)$talent->career());
        $this->assertSame((string)$createTalentInfo->imageLink, (string)$talent->imageLink());
        $this->assertSame($createTalentInfo->relevantVideoLinks->toStringArray(), $talent->relevantVideoLinks()->toStringArray());
        $this->assertSame($createTalentInfo->status, $talent->status());
    }

    /**
     * 正常系：COLLABORATORがメンバーを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedCollaborator(): void
    {
        $createTalentInfo = $this->createCreateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, null, [], []);

        $input = new CreateTalentInput(
            $createTalentInfo->publishedTalentIdentifier,
            $createTalentInfo->language,
            $createTalentInfo->name,
            $createTalentInfo->realName,
            $createTalentInfo->agencyIdentifier,
            $createTalentInfo->groupIdentifiers,
            $createTalentInfo->birthday,
            $createTalentInfo->career,
            $createTalentInfo->base64EncodedImage,
            $createTalentInfo->relevantVideoLinks,
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
            ->with($createTalentInfo->base64EncodedImage)
            ->andReturn($createTalentInfo->imageLink);

        $talentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createTalentInfo->language, $createTalentInfo->name)
            ->andReturn($createTalentInfo->draftTalent);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($createTalentInfo->publishedTalentIdentifier)
            ->andReturn(null);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($createTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $useCase = $this->app->make(CreateTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORがメンバーを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedAgencyActor(): void
    {
        $createTalentInfo = $this->createCreateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string)$createTalentInfo->agencyIdentifier;
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, [],  []);

        $input = new CreateTalentInput(
            $createTalentInfo->publishedTalentIdentifier,
            $createTalentInfo->language,
            $createTalentInfo->name,
            $createTalentInfo->realName,
            $createTalentInfo->agencyIdentifier,
            $createTalentInfo->groupIdentifiers,
            $createTalentInfo->birthday,
            $createTalentInfo->career,
            $createTalentInfo->base64EncodedImage,
            $createTalentInfo->relevantVideoLinks,
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
            ->with($createTalentInfo->base64EncodedImage)
            ->andReturn($createTalentInfo->imageLink);

        $talentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createTalentInfo->language, $createTalentInfo->name)
            ->andReturn($createTalentInfo->draftTalent);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($createTalentInfo->publishedTalentIdentifier)
            ->andReturn(null);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($createTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $useCase = $this->app->make(CreateTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：GROUP_ACTORがメンバーを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedGroupActor(): void
    {
        $createTalentInfo = $this->createCreateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string)$createTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $createTalentInfo->groupIdentifiers);
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, $groupIds,  []);

        $input = new CreateTalentInput(
            $createTalentInfo->publishedTalentIdentifier,
            $createTalentInfo->language,
            $createTalentInfo->name,
            $createTalentInfo->realName,
            $createTalentInfo->agencyIdentifier,
            $createTalentInfo->groupIdentifiers,
            $createTalentInfo->birthday,
            $createTalentInfo->career,
            $createTalentInfo->base64EncodedImage,
            $createTalentInfo->relevantVideoLinks,
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
            ->with($createTalentInfo->base64EncodedImage)
            ->andReturn($createTalentInfo->imageLink);

        $talentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createTalentInfo->language, $createTalentInfo->name)
            ->andReturn($createTalentInfo->draftTalent);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($createTalentInfo->publishedTalentIdentifier)
            ->andReturn(null);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($createTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $useCase = $this->app->make(CreateTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：TALENT_ACTORがメンバーを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testAuthorizedTalentActor(): void
    {
        $createTalentInfo = $this->createCreateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyId = (string)$createTalentInfo->agencyIdentifier;
        $groupIds = array_map(static fn ($groupId) => (string)$groupId, $createTalentInfo->groupIdentifiers);
        $talentId = (string)$createTalentInfo->talentIdentifier;
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, $agencyId, $groupIds,  [$talentId]);

        $input = new CreateTalentInput(
            $createTalentInfo->publishedTalentIdentifier,
            $createTalentInfo->language,
            $createTalentInfo->name,
            $createTalentInfo->realName,
            $createTalentInfo->agencyIdentifier,
            $createTalentInfo->groupIdentifiers,
            $createTalentInfo->birthday,
            $createTalentInfo->career,
            $createTalentInfo->base64EncodedImage,
            $createTalentInfo->relevantVideoLinks,
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
            ->with($createTalentInfo->base64EncodedImage)
            ->andReturn($createTalentInfo->imageLink);

        $talentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createTalentInfo->language, $createTalentInfo->name)
            ->andReturn($createTalentInfo->draftTalent);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($createTalentInfo->publishedTalentIdentifier)
            ->andReturn(null);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($createTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $useCase = $this->app->make(CreateTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系：SENIOR_COLLABORATORがメンバーを作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithSeniorCollaborator(): void
    {
        $createTalentInfo = $this->createCreateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new CreateTalentInput(
            $createTalentInfo->publishedTalentIdentifier,
            $createTalentInfo->language,
            $createTalentInfo->name,
            $createTalentInfo->realName,
            $createTalentInfo->agencyIdentifier,
            $createTalentInfo->groupIdentifiers,
            $createTalentInfo->birthday,
            $createTalentInfo->career,
            $createTalentInfo->base64EncodedImage,
            $createTalentInfo->relevantVideoLinks,
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
            ->with($createTalentInfo->base64EncodedImage)
            ->andReturn($createTalentInfo->imageLink);

        $talentFactory = Mockery::mock(DraftTalentFactoryInterface::class);
        $talentFactory->shouldReceive('create')
            ->once()
            ->with($principalIdentifier, $createTalentInfo->language, $createTalentInfo->name)
            ->andReturn($createTalentInfo->draftTalent);

        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $talentRepository->shouldReceive('findById')
            ->once()
            ->with($createTalentInfo->publishedTalentIdentifier)
            ->andReturn(null);

        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);
        $draftTalentRepository->shouldReceive('save')
            ->once()
            ->with($createTalentInfo->draftTalent)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftTalentFactoryInterface::class, $talentFactory);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $useCase = $this->app->make(CreateTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * 異常系：NONEロールがメンバーを作成しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $createTalentInfo = $this->createCreateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new CreateTalentInput(
            $createTalentInfo->publishedTalentIdentifier,
            $createTalentInfo->language,
            $createTalentInfo->name,
            $createTalentInfo->realName,
            $createTalentInfo->agencyIdentifier,
            $createTalentInfo->groupIdentifiers,
            $createTalentInfo->birthday,
            $createTalentInfo->career,
            $createTalentInfo->base64EncodedImage,
            $createTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $this->expectException(UnauthorizedException::class);
        $useCase = $this->app->make(CreateTalentInterface::class);
        $useCase->process($input);
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
        $createTalentInfo = $this->createCreateTalentInfo();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new CreateTalentInput(
            $createTalentInfo->publishedTalentIdentifier,
            $createTalentInfo->language,
            $createTalentInfo->name,
            $createTalentInfo->realName,
            $createTalentInfo->agencyIdentifier,
            $createTalentInfo->groupIdentifiers,
            $createTalentInfo->birthday,
            $createTalentInfo->career,
            $createTalentInfo->base64EncodedImage,
            $createTalentInfo->relevantVideoLinks,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $talentRepository = Mockery::mock(TalentRepositoryInterface::class);
        $draftTalentRepository = Mockery::mock(DraftTalentRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(TalentRepositoryInterface::class, $talentRepository);
        $this->app->instance(DraftTalentRepositoryInterface::class, $draftTalentRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $useCase = $this->app->make(CreateTalentInterface::class);
        $useCase->process($input);
    }

    /**
     * @return CreateTalentTestData
     * @throws ExceedMaxRelevantVideoLinksException
     */
    private function createCreateTalentInfo(): CreateTalentTestData
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
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $status,
        );

        $version = new Version(1);
        $publishedTalent = new Talent(
            $publishedTalentIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $realName,
            $agencyIdentifier,
            $groupIdentifiers,
            $birthday,
            $career,
            $imageLink,
            $relevantVideoLinks,
            $version,
        );

        return new CreateTalentTestData(
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
            $publishedTalent,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class CreateTalentTestData
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
        public Talent $publishedTalent,
    ) {
    }
}
