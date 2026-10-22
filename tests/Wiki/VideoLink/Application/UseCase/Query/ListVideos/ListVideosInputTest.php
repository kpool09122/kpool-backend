<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query\ListVideos;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInput;

class ListVideosInputTest extends TestCase
{
    public function testPreservesInputValueObjects(): void
    {
        $slug = new Slug('gr-test');
        $input = new ListVideosInput($slug, Language::JAPANESE, ResourceType::GROUP);
        $this->assertSame($slug, $input->slug());
        $this->assertSame(Language::JAPANESE, $input->language());
        $this->assertSame(ResourceType::GROUP, $input->resourceType());
    }
}
