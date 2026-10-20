<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Application\UseCase\Query\FreeMaterialSiteReadModel;
use Source\Wiki\Image\Application\UseCase\Query\SearchFreeMaterials\SearchFreeMaterialsOutput;

class SearchFreeMaterialsOutputTest extends TestCase
{
    public function testEmptyResultSerializesAsSitesList(): void
    {
        $output = new SearchFreeMaterialsOutput();
        $output->output([]);
        self::assertSame(['sites' => []], $output->toArray());
    }

    public function testPreservesAttributionAndEvidenceContract(): void
    {
        $output = new SearchFreeMaterialsOutput();
        $output->output([new FreeMaterialSiteReadModel('Commons', 'https://commons.wikimedia.org/wiki/File:Photo', 'Target matches', 'CC BY', 'https://commons.wikimedia.org/license')]);
        self::assertSame(['sites' => [['siteName' => 'Commons', 'pageUrl' => 'https://commons.wikimedia.org/wiki/File:Photo', 'reason' => 'Target matches', 'usageEvidence' => 'CC BY', 'usageEvidenceUrl' => 'https://commons.wikimedia.org/license']]], $output->toArray());
    }
}
