<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki;

use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInput;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Tests\TestCase;

class GetGroupDraftWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $wikiIdentifier = new DraftWikiIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f002');
        $input = new GetGroupDraftWikiInput($wikiIdentifier);

        $this->assertSame((string) $wikiIdentifier, (string) $input->wikiIdentifier());
    }
}
