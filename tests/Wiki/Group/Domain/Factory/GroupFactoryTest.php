<?php

namespace Tests\Wiki\Group\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidValidator;
use Businesses\Wiki\Group\Domain\Factory\GroupFactory;
use Businesses\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tests\TestCase;

class GroupFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $groupFactory = $this->app->make(GroupFactoryInterface::class);
        $this->assertInstanceOf(GroupFactory::class, $groupFactory);
    }

    /**
     * 正常系: Group Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $name = new GroupName('TWICE');
        $groupFactory = $this->app->make(GroupFactoryInterface::class);
        $group = $groupFactory->create($name);
        $this->assertTrue(UlidValidator::isValid((string)$group->groupIdentifier()));
        $this->assertSame((string)$name, (string)$group->name());
        $this->assertNull($group->companyIdentifier());
        $this->assertSame('', (string)$group->description());
        $this->assertSame([], $group->songIdentifiers());
        $this->assertNull($group->imageLink());
    }
}
