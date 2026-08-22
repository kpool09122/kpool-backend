<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisInput;
use Tests\TestCase;

class ListMyOwnedWikisInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $input = new ListMyOwnedWikisInput(
            accountIdentifier: new AccountIdentifier('01965bb2-bcc9-7c6f-8b90-89f7f217f101'),
            accountCategory: AccountCategory::AGENCY,
            perPage: 25,
        );

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f101', (string) $input->accountIdentifier());
        $this->assertSame(AccountCategory::AGENCY, $input->accountCategory());
        $this->assertSame(25, $input->perPage());
    }
}
