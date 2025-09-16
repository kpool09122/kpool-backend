<?php

namespace Tests\Wiki\Member\Domain\Factory;

use Businesses\Wiki\Member\Domain\Factory\MemberFactory;
use Businesses\Wiki\Member\Domain\Factory\MemberFactoryInterface;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tests\TestCase;

class MemberFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $memberFactory = $this->app->make(MemberFactoryInterface::class);
        $this->assertInstanceOf(MemberFactory::class, $memberFactory);
    }

    /**
     * 正常系: Member Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $name = new MemberName('채영');
        $memberFactory = $this->app->make(MemberFactoryInterface::class);
        $member = $memberFactory->create($name);
        $this->assertSame((string)$name, (string)$member->name());
        $this->assertNull($member->groupIdentifier());
        $this->assertNull($member->birthday());
        $this->assertSame('', (string)$member->career());
        $this->assertNull($member->imageLink());
    }
}
