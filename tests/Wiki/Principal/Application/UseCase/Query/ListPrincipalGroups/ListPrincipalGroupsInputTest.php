<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Query\ListPrincipalGroups;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Tests\Helper\StrTestHelper;

class ListPrincipalGroupsInputTest extends TestCase
{
    public function test__construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $input = new ListPrincipalGroupsInput($accountIdentifier);

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
    }
}
