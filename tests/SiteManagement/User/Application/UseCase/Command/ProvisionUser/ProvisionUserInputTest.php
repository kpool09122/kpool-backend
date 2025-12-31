<?php

declare(strict_types=1);

namespace Tests\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser\ProvisionUserInput;
use Tests\Helper\StrTestHelper;

class ProvisionUserInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $input = new ProvisionUserInput($identityIdentifier);

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
    }
}
