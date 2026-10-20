<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Application\UseCase\Query\FreeMaterialSiteReadModel;

class FreeMaterialSiteReadModelTest extends TestCase
{
    public function testAttributionAndEvidenceFieldsAreIndependent(): void
    {
        $site = new FreeMaterialSiteReadModel('Source name', 'https://commons.wikimedia.org/photo', 'Reason', 'Evidence', 'https://commons.wikimedia.org/license');
        self::assertSame(['siteName' => 'Source name', 'pageUrl' => 'https://commons.wikimedia.org/photo', 'reason' => 'Reason', 'usageEvidence' => 'Evidence', 'usageEvidenceUrl' => 'https://commons.wikimedia.org/license'], $site->toArray());
    }
}
