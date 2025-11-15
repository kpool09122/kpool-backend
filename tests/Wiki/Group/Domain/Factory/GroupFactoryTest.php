<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Factory\GroupFactory;
use Source\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $groupFactory = $this->app->make(GroupFactoryInterface::class);
        $group = $groupFactory->create($translationSetIdentifier, $translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$group->groupIdentifier()));
        $this->assertSame((string)$translationSetIdentifier, (string)$group->translationSetIdentifier());
        $this->assertSame($translation->value, $group->translation()->value);
        $this->assertSame((string)$name, (string)$group->name());
        $this->assertNull($group->agencyIdentifier());
        $this->assertSame('', (string)$group->description());
        $this->assertSame([], $group->songIdentifiers());
        $this->assertNull($group->imagePath());
    }
}
