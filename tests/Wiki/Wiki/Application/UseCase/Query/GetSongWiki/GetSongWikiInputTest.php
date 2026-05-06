<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\GetSongWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongWiki\GetSongWikiInput;
use Tests\TestCase;

class GetSongWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $slug = new Slug('sg-signal');
        $language = Language::KOREAN;
        $input = new GetSongWikiInput($slug, $language);

        $this->assertSame((string) $slug, (string) $input->slug());
        $this->assertSame($language, $input->language());
    }
}
