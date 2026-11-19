<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;

readonly class SubmitWikiInput implements SubmitWikiInputPort
{
    /** @param list<SubmitWikiTarget> $targets */
    public function __construct(
        private array $targets,
        private PrincipalIdentifier $principalIdentifier,
        private VisitorLocation $visitorLocation = new VisitorLocation(),
    ) {
        if (! array_is_list($targets) || count($targets) < 1 || count($targets) > 100) {
            throw new InvalidArgumentException('Submit between 1 and 100 wiki drafts.');
        }
        $identifiers = array_map(static fn (SubmitWikiTarget $target): string => strtolower((string) $target->wikiIdentifier()), $targets);
        if (count(array_unique($identifiers)) !== count($identifiers)) {
            throw new InvalidArgumentException('Duplicate wiki drafts cannot be submitted.');
        }
    }

    /** @return list<SubmitWikiTarget> */
    public function targets(): array
    {
        return $this->targets;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function visitorLocation(): VisitorLocation
    {
        return $this->visitorLocation;
    }
}
