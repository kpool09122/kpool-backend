<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki\GetSongDraftWikiInput;
use Tests\TestCase;

class GetSongDraftWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $slug = new Slug('signal');
        $language = Language::KOREAN;
        $input = new GetSongDraftWikiInput($slug, $language);

        $this->assertSame((string) $slug, (string) $input->slug());
        $this->assertSame($language, $input->language());
    }
}
