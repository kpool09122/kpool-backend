<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki\SubmitWikiTarget;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitWikiInputTest extends TestCase
{
    #[DataProvider('counts')]
    public function testSubmissionCount(int $count, bool $valid): void
    {
        $targets = array_map(static fn (): SubmitWikiTarget => new SubmitWikiTarget(
            new DraftWikiIdentifier(StrTestHelper::generateUuid()),
            ResourceType::GROUP,
        ), array_fill(0, $count, null));
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $visitorLocation = new VisitorLocation('JP', '01');
        if (! $valid) {
            $this->expectException(InvalidArgumentException::class);
        }
        $input = new SubmitWikiInput($targets, $principalIdentifier, $visitorLocation);
        $this->assertSame($targets, $input->targets());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($visitorLocation, $input->visitorLocation());
    }

    /** @return list<array{int, bool}> */
    public static function counts(): array
    {
        return [[0, false], [1, true], [100, true], [101, false]];
    }

    public function testDuplicateIdentifiersAreRejected(): void
    {
        $id = StrTestHelper::generateUuid();
        $this->expectException(InvalidArgumentException::class);
        new SubmitWikiInput([
            new SubmitWikiTarget(new DraftWikiIdentifier($id), ResourceType::GROUP),
            new SubmitWikiTarget(new DraftWikiIdentifier(strtoupper($id)), ResourceType::GROUP),
        ], new PrincipalIdentifier(StrTestHelper::generateUuid()));
    }
}
