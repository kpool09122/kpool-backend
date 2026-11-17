<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInput;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Tests\TestCase;

class GetTalentDraftWikiInputTest extends TestCase
{
    public function test__construct(): void
    {
        $wikiIdentifier = new DraftWikiIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f301');
        $principalIdentifier = new PrincipalIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f702');
        $input = new GetTalentDraftWikiInput($wikiIdentifier, $principalIdentifier);

        $this->assertSame($wikiIdentifier, $input->wikiIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
