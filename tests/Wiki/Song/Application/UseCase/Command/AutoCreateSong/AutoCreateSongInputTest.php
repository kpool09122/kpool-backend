<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\AutoCreateSong;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Application\UseCase\Command\AutoCreateSong\AutoCreateSongInput;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutoSongCreationPayload;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;

class AutoCreateSongInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutoSongCreationPayload(
            Language::JAPANESE,
            new SongName('Auto Song'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier(StrTestHelper::generateUuid()),
            new TalentIdentifier(StrTestHelper::generateUuid()),
        );
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new AutoCreateSongInput($payload, $principalIdentifier);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
