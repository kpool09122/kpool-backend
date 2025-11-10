<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactory;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
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
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new GroupName('TWICE');
        $groupFactory = $this->app->make(DraftGroupFactoryInterface::class);
        $group = $groupFactory->create($editorIdentifier, $translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$group->groupIdentifier()));
        $this->assertNull($group->publishedGroupIdentifier());
        $this->assertTrue(UlidValidator::isValid((string)$group->translationSetIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$group->editorIdentifier());
        $this->assertSame($translation->value, $group->translation()->value);
        $this->assertSame((string)$name, (string)$group->name());
        $this->assertNull($group->agencyIdentifier());
        $this->assertSame('', (string)$group->description());
        $this->assertSame([], $group->songIdentifiers());
        $this->assertNull($group->imagePath());
        $this->assertSame(ApprovalStatus::Pending, $group->status());

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $group = $groupFactory->create($editorIdentifier, $translation, $name, $translationSetIdentifier);
        $this->assertSame((string)$translationSetIdentifier, (string)$group->translationSetIdentifier());
    }
}
