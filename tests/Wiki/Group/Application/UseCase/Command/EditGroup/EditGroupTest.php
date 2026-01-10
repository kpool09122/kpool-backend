<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\EditGroup;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\UseCase\Command\EditGroup\EditGroup;
use Source\Wiki\Group\Application\UseCase\Command\EditGroup\EditGroupInput;
use Source\Wiki\Group\Application\UseCase\Command\EditGroup\EditGroupInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class EditGroupTest extends TestCase
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
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $editGroup = $this->app->make(EditGroupInterface::class);
        $this->assertInstanceOf(EditGroup::class, $editGroup);
    }

    /**
     * 正常系：正しくGroup Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $dummyData = $this->createDummyEditGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new EditGroupInput(
            $dummyData->groupIdentifier,
            $dummyData->name,
            $dummyData->agencyIdentifier,
            $dummyData->description,
            $dummyData->base64EncodedImage,
            $principalIdentifier,
        );

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($dummyData->base64EncodedImage)
            ->andReturn($dummyData->imagePath);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($dummyData->group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyData->groupIdentifier)
            ->andReturn($dummyData->group);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $editGroup = $this->app->make(EditGroupInterface::class);
        $group = $editGroup->process($input);
        $this->assertSame((string)$dummyData->groupIdentifier, (string)$group->groupIdentifier());
        $this->assertSame((string)$dummyData->publishedGroupIdentifier, (string)$group->publishedGroupIdentifier());
        $this->assertSame((string)$dummyData->translationSetIdentifier, (string)$group->translationSetIdentifier());
        $this->assertSame((string)$dummyData->editorIdentifier, (string)$group->editorIdentifier());
        $this->assertSame($dummyData->language->value, $group->language()->value);
        $this->assertSame((string)$dummyData->name, (string)$group->name());
        $this->assertSame($dummyData->normalizedName, $group->normalizedName());
        $this->assertSame((string)$dummyData->agencyIdentifier, (string)$group->agencyIdentifier());
        $this->assertSame((string)$dummyData->description, (string)$group->description());
        $this->assertSame((string)$dummyData->imagePath, (string)$group->imagePath());
        $this->assertSame($dummyData->status, $group->status());
    }

    /**
     * 異常系：指定したIDに紐づくGroupが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundGroup(): void
    {
        $dummyData = $this->createDummyEditGroup();

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new EditGroupInput(
            $dummyData->groupIdentifier,
            $dummyData->name,
            $dummyData->agencyIdentifier,
            $dummyData->description,
            $dummyData->base64EncodedImage,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyData->groupIdentifier)
            ->andReturn(null);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);
        $this->expectException(GroupNotFoundException::class);
        $editGroup = $this->app->make(EditGroupInterface::class);
        $editGroup->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummyData = $this->createDummyEditGroup(base64EncodedImage: null);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new EditGroupInput(
            $dummyData->groupIdentifier,
            $dummyData->name,
            $dummyData->agencyIdentifier,
            $dummyData->description,
            $dummyData->base64EncodedImage,
            $principalIdentifier,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyData->groupIdentifier)
            ->andReturn($dummyData->group);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);

        $this->expectException(PrincipalNotFoundException::class);
        $editGroup = $this->app->make(EditGroupInterface::class);
        $editGroup->process($input);
    }

    /**
     * 異常系：権限がない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws GroupNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testUnauthorized(): void
    {
        $dummyData = $this->createDummyEditGroup(base64EncodedImage: null);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);

        $input = new EditGroupInput(
            $dummyData->groupIdentifier,
            $dummyData->name,
            $dummyData->agencyIdentifier,
            $dummyData->description,
            $dummyData->base64EncodedImage,
            $principalIdentifier,
        );

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $groupRepository = Mockery::mock(DraftGroupRepositoryInterface::class);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($dummyData->groupIdentifier)
            ->andReturn($dummyData->group);

        $imageService = Mockery::mock(ImageServiceInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(DraftGroupRepositoryInterface::class, $groupRepository);

        $this->expectException(UnauthorizedException::class);
        $editGroup = $this->app->make(EditGroupInterface::class);
        $editGroup->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @param string|null $base64EncodedImage
     * @return EditGroupTestData
     */
    private function createDummyEditGroup(?string $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII='): EditGroupTestData
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $publishedGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $status = ApprovalStatus::Pending;

        $group = new DraftGroup(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
            $imagePath,
            $status,
        );

        return new EditGroupTestData(
            $groupIdentifier,
            $publishedGroupIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
            $base64EncodedImage,
            $imagePath,
            $status,
            $group,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class EditGroupTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public GroupIdentifier          $groupIdentifier,
        public GroupIdentifier          $publishedGroupIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public GroupName                $name,
        public string                   $normalizedName,
        public AgencyIdentifier         $agencyIdentifier,
        public Description              $description,
        public ?string                  $base64EncodedImage,
        public ImagePath                $imagePath,
        public ApprovalStatus           $status,
        public DraftGroup               $group,
    ) {
    }
}
