<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\TalentFactory;
use Source\Wiki\Talent\Domain\Factory\TalentFactoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $talentFactory = $this->app->make(TalentFactoryInterface::class);
        $this->assertInstanceOf(TalentFactory::class, $talentFactory);
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new TalentName('채영');
        $talentFactory = $this->app->make(TalentFactoryInterface::class);
        $talent = $talentFactory->create($translationSetIdentifier, $translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$talent->talentIdentifier()));
        $this->assertSame((string)$translationSetIdentifier, (string)$talent->translationSetIdentifier());
        $this->assertSame($translation->value, $talent->translation()->value);
        $this->assertSame((string)$name, (string)$talent->name());
        $this->assertSame('', (string)$talent->realName());
        $this->assertSame([], $talent->groupIdentifiers());
        $this->assertNull($talent->birthday());
        $this->assertSame('', (string)$talent->career());
        $this->assertNull($talent->imageLink());
    }
}
