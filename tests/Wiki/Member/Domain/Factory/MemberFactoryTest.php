<?php

declare(strict_types=1);

namespace Tests\Wiki\Member\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\Factory\MemberFactory;
use Source\Wiki\Member\Domain\Factory\MemberFactoryInterface;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Tests\Helper\StrTestHelper;
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
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function testCreate(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new MemberName('채영');
        $memberFactory = $this->app->make(MemberFactoryInterface::class);
        $member = $memberFactory->create($translationSetIdentifier, $translation, $name);
        $this->assertTrue(UlidValidator::isValid((string)$member->memberIdentifier()));
        $this->assertSame((string)$translationSetIdentifier, (string)$member->translationSetIdentifier());
        $this->assertSame($translation->value, $member->translation()->value);
        $this->assertSame((string)$name, (string)$member->name());
        $this->assertSame('', (string)$member->realName());
        $this->assertSame([], $member->groupIdentifiers());
        $this->assertNull($member->birthday());
        $this->assertSame('', (string)$member->career());
        $this->assertNull($member->imageLink());
    }
}
