<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactory;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftTalentFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $talentFactory = $this->app->make(DraftTalentFactoryInterface::class);
        $this->assertInstanceOf(DraftTalentFactory::class, $talentFactory);
    }

    /**
     * 正常系: Talent Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testCreate(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $talentFactory = $this->app->make(DraftTalentFactoryInterface::class);
        $talent = $talentFactory->create($editorIdentifier, $translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$talent->talentIdentifier()));
        $this->assertNull($talent->publishedTalentIdentifier());
        $this->assertSame((string)$editorIdentifier, (string)$talent->editorIdentifier());
        $this->assertSame($translation->value, $talent->translation()->value);
        $this->assertSame((string)$name, (string)$talent->name());
        $this->assertSame('', (string)$talent->realName());
        $this->assertNull($talent->agencyIdentifier());
        $this->assertSame([], $talent->groupIdentifiers());
        $this->assertNull($talent->birthday());
        $this->assertSame('', (string)$talent->career());
        $this->assertNull($talent->imageLink());
        $this->assertSame(ApprovalStatus::Pending, $talent->status());
    }
}
