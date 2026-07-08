<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisInput;
use Tests\TestCase;

class SearchMasterWikisInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $input = new SearchMasterWikisInput(
            language: Language::KOREAN,
            resourceType: ResourceType::TALENT,
            keyword: ' minji ',
            limit: 20,
        );

        $this->assertSame(Language::KOREAN, $input->language());
        $this->assertSame(ResourceType::TALENT, $input->resourceType());
        $this->assertSame('minji', $input->keyword());
        $this->assertSame(20, $input->limit());
    }

    public function testDefaultLimit(): void
    {
        $input = new SearchMasterWikisInput(Language::KOREAN, ResourceType::GROUP, 'ive');

        $this->assertSame(10, $input->limit());
    }

    public function testRejectsEmptyKeyword(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('keyword is required.');

        new SearchMasterWikisInput(Language::KOREAN, ResourceType::GROUP, '   ');
    }

    public function testRejectsUnsupportedResourceType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('resourceType is not supported.');

        new SearchMasterWikisInput(Language::KOREAN, ResourceType::IMAGE, 'image');
    }

    public function testRejectsLimitOutsideRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('limit must be between 1 and 50.');

        new SearchMasterWikisInput(Language::KOREAN, ResourceType::GROUP, 'ive', 51);
    }
}
