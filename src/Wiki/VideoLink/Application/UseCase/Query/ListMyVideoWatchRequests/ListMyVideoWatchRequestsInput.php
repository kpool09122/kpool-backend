<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;

readonly class ListMyVideoWatchRequestsInput implements ListMyVideoWatchRequestsInputPort
{
    /** @param list<VideoLinkIdentifier> $videoLinkIdentifiers */
    public function __construct(private array $videoLinkIdentifiers, private PrincipalIdentifier $principalIdentifier)
    {
        if ($videoLinkIdentifiers === [] || count($videoLinkIdentifiers) > 100) {
            throw new InvalidArgumentException('Between 1 and 100 video link identifiers are required.');
        }
    }

    /** @return list<VideoLinkIdentifier> */
    public function videoLinkIdentifiers(): array
    {
        return $this->videoLinkIdentifiers;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
