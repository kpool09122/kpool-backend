<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisInput;
use Tests\TestCase;

class SearchTranslationSetMasterWikisInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $input = new SearchTranslationSetMasterWikisInput(
            resourceType: ResourceType::TALENT,
            keyword: ' minji ',
            limit: 20,
        );

        $this->assertSame(ResourceType::TALENT, $input->resourceType());
        $this->assertSame('minji', $input->keyword());
        $this->assertSame(20, $input->limit());
    }

    public function testDefaultLimit(): void
    {
        $input = new SearchTranslationSetMasterWikisInput(ResourceType::GROUP, 'ive');

        $this->assertSame(10, $input->limit());
    }

    public function testRejectsEmptyKeyword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('keyword is required.');

        new SearchTranslationSetMasterWikisInput(ResourceType::GROUP, '   ');
    }

    public function testRejectsUnsupportedResourceType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('resourceType is not supported.');

        new SearchTranslationSetMasterWikisInput(ResourceType::IMAGE, 'image');
    }

    public function testRejectsLimitOutsideRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('limit must be between 1 and 50.');

        new SearchTranslationSetMasterWikisInput(ResourceType::GROUP, 'ive', 51);
    }
}
