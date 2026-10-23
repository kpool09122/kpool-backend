<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\Service;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Service\VideoLinkCollectionPolicy;

class VideoLinkCollectionPolicyTest extends TestCase
{
    public function testSupportsOnlyExistingThreeCollectionTypes(): void
    {
        foreach (ResourceType::cases() as $type) {
            $this->assertSame(in_array($type, [ResourceType::GROUP, ResourceType::SONG, ResourceType::TALENT], true), VideoLinkCollectionPolicy::supports($type));
        }
    }
}
