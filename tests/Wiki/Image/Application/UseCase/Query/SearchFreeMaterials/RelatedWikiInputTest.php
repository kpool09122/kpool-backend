<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\RelatedWikiInput;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class RelatedWikiInputTest extends TestCase
{
    public function testPreservesUnsavedWikiValues(): void
    {
        $identifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $input = new RelatedWikiInput($identifier, '  Edited group  ', ResourceType::GROUP, 'member_of');

        self::assertSame($identifier, $input->identifier);
        self::assertSame('  Edited group  ', $input->title);
        self::assertSame(ResourceType::GROUP, $input->resourceType);
        self::assertSame('member_of', $input->relationType);
    }
}
