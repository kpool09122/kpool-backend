<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Application\UseCase\Command\CollectVideoLinks\CollectVideoLinksOutput;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

class CollectVideoLinksOutputTest extends TestCase
{
    public function testReportsSuccessWithResourceAndCount(): void
    {
        $identifier = new WikiIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $output = new CollectVideoLinksOutput();
        $output->success(ResourceType::TALENT, $identifier, 3);
        $this->assertTrue($output->processed);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame($identifier, $output->wikiIdentifier);
        $this->assertSame(3, $output->collectedCount);
        $this->assertSame('Successfully collected 3 videos', $output->message);
    }

    public function testReportsReasonsForSkippingCollection(): void
    {
        $identifier = new WikiIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $output = new CollectVideoLinksOutput();
        $output->noTargetResource();
        $this->assertFalse($output->processed);
        $this->assertNull($output->resourceType);
        $this->assertNull($output->wikiIdentifier);
        $this->assertSame('No target resource found for video link collection', $output->message);
        $output->recentlyCollected(ResourceType::TALENT, $identifier);
        $this->assertFalse($output->processed);
        $this->assertSame($identifier, $output->wikiIdentifier);
        $this->assertSame(ResourceType::TALENT, $output->resourceType);
        $this->assertSame('Resource was collected within the last month', $output->message);
        $output->resourceNotFound(ResourceType::TALENT, $identifier);
        $this->assertFalse($output->processed);
        $this->assertSame($identifier, $output->wikiIdentifier);
        $this->assertSame('Resource not found', $output->message);
    }
}
