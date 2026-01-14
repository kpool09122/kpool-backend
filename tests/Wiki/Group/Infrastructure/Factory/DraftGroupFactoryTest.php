<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Infrastructure\Factory\DraftGroupFactory;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftGroupFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $groupFactory = $this->app->make(DraftGroupFactoryInterface::class);
        $this->assertInstanceOf(DraftGroupFactory::class, $groupFactory);
    }

    /**
     * 正常系: DraftGroup Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $groupFactory = $this->app->make(DraftGroupFactoryInterface::class);
        $group = $groupFactory->create($editorIdentifier, $language, $name);
        $this->assertTrue(UuidValidator::isValid((string)$group->groupIdentifier()));
        $this->assertNull($group->publishedGroupIdentifier());
        $this->assertTrue(UuidValidator::isValid((string)$group->translationSetIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$group->editorIdentifier());
        $this->assertSame($language->value, $group->language()->value);
        $this->assertSame((string)$name, (string)$group->name());
        $this->assertSame('twice', $group->normalizedName());
        $this->assertNull($group->agencyIdentifier());
        $this->assertSame('', (string)$group->description());
        $this->assertSame(ApprovalStatus::Pending, $group->status());

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $group = $groupFactory->create($editorIdentifier, $language, $name, $translationSetIdentifier);
        $this->assertSame((string)$translationSetIdentifier, (string)$group->translationSetIdentifier());
    }
}
