<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Infrastructure\Factory\GroupFactory;
use Tests\Helper\StrTestHelper;
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $groupFactory = $this->app->make(GroupFactoryInterface::class);
        $group = $groupFactory->create($translationSetIdentifier, $language, $name);
        $this->assertTrue(UuidValidator::isValid((string)$group->groupIdentifier()));
        $this->assertSame((string)$translationSetIdentifier, (string)$group->translationSetIdentifier());
        $this->assertSame($language->value, $group->language()->value);
        $this->assertSame((string)$name, (string)$group->name());
        $this->assertSame('twice', $group->normalizedName());
        $this->assertNull($group->agencyIdentifier());
        $this->assertSame('', (string)$group->description());
    }
}
