<?php

namespace Tests\Wiki\Group\UseCase\Command\CreateGroup;

use Businesses\Shared\Service\ImageServiceInterface;
use Businesses\Shared\Service\Ulid\UlidValidator;
use Businesses\Shared\ValueObject\ImagePath;
use Businesses\Wiki\Group\Domain\Entity\Group;
use Businesses\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Businesses\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Businesses\Wiki\Group\Domain\ValueObject\CompanyIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\Description;
use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;
use Businesses\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Businesses\Wiki\Group\UseCase\Command\CreateGroup\CreateGroup;
use Businesses\Wiki\Group\UseCase\Command\CreateGroup\CreateGroupInput;
use Businesses\Wiki\Group\UseCase\Command\CreateGroup\CreateGroupInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateGroupTest extends TestCase
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
        $createGroup = $this->app->make(CreateGroupInterface::class);
        $this->assertInstanceOf(CreateGroup::class, $createGroup);
    }

    /**
     * 正常系：正しくMember Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $name = new GroupName('TWICE');
        $companyIdentifier = new CompanyIdentifier(StrTestHelper::generateUlid());
        $description = new Description('### 트와이스: 전 세계를 사로잡은 9인조 걸그룹
트와이스(TWICE)는 2015년 한국의 서바이벌 오디션 프로그램 \'SIXTEEN\'을 통해 결성된 JYP 엔터테인먼트 소속의 9인조 걸그룹입니다. 멤버는 한국 출신 5명(나연, 정연, 지효, 다현, 채영), 일본 출신 3명(모모, 사나, 미나), 대만 출신 1명(쯔위)의 다국적 구성으로, 다양한 매력이 모여 있습니다.
그룹명은 \'좋은 음악으로 한번, 멋진 퍼포먼스로 두 번 감동을 준다\'는 의미를 담고 있습니다. 그 이름처럼 데뷔곡 \'OOH-AHH하게\' 이후, \'CHEER UP\', \'TT\', \'LIKEY\', \'What is Love?\', \'FANCY\' 등 수많은 히트곡을 연달아 발표했습니다. 특히 \'TT\'에서 보여준 우는 표정을 표현한 \'TT 포즈\'는 일본에서도 사회 현상이 될 정도로 큰 인기를 얻었습니다.
데뷔 초의 밝고 귀여운 콘셉트에서 해마다 성장을 거듭하며, 세련되고 멋진 퍼포먼스까지 다채로운 모습을 보여주고 있습니다. 중독성 있는 멜로디와 따라 하기 쉬운 안무가 특징으로, 폭넓은 세대로부터 지지를 받고 있습니다. 한국이나 일본뿐만 아니라, 세계적인 스타디움 투어를 성공시키는 등 K팝을 대표하는 최정상 그룹으로서 지금도 전 세계 팬들을 계속해서 사로잡고 있습니다. 팬덤명은 \'원스(ONCE)\'입니다.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $base64EncodedImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $input = new CreateGroupInput(
            $name,
            $companyIdentifier,
            $description,
            $songIdentifiers,
            $base64EncodedImage,
        );

        $imagePath = new ImagePath('/resources/public/images/before.webp');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedImage)
            ->andReturn($imagePath);

        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $group = new Group(
            $groupIdentifier,
            $name,
            $companyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
        );
        $groupFactory = Mockery::mock(GroupFactoryInterface::class);
        $groupFactory->shouldReceive('create')
            ->once()
            ->with($name)
            ->andReturn($group);

        $groupRepository = Mockery::mock(GroupRepositoryInterface::class);
        $groupRepository->shouldReceive('save')
            ->once()
            ->with($group)
            ->andReturn(null);
        $groupRepository->shouldReceive('findById')
            ->once()
            ->with($groupIdentifier)
            ->andReturn($group);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(GroupFactoryInterface::class, $groupFactory);
        $this->app->instance(GroupRepositoryInterface::class, $groupRepository);
        $createGroup = $this->app->make(CreateGroupInterface::class);
        $group = $createGroup->process($input);
        $this->assertTrue(UlidValidator::isValid((string)$group->groupIdentifier()));
        $this->assertSame((string)$name, (string)$group->name());
        $this->assertSame((string)$companyIdentifier, (string)$group->companyIdentifier());
        $this->assertSame((string)$description, (string)$group->description());
        $this->assertSame($songIdentifiers, $group->songIdentifiers());
        $this->assertSame((string)$imagePath, (string)$group->imageLink());
    }
}
