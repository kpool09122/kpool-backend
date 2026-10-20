<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\RelatedWikiInput;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsInput;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class SearchFreeMaterialsInputTest extends TestCase
{
    public function testPreservesInputValuesAndValueObjects(): void
    {
        $identifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $related = new RelatedWikiInput(new WikiIdentifier(StrTestHelper::generateUuid()), 'New group', ResourceType::GROUP, 'member_of');
        $input = new SearchFreeMaterialsInput($identifier, '  Edited title  ', 'Display name', ResourceType::TALENT, Language::ENGLISH, [$related]);
        self::assertSame($identifier, $input->principalIdentifier());
        self::assertSame('  Edited title  ', $input->title());
        self::assertSame('Display name', $input->displayName());
        self::assertSame(ResourceType::TALENT, $input->resourceType());
        self::assertSame(Language::ENGLISH, $input->language());
        self::assertSame([$related], $input->relatedWikis());
    }
}
