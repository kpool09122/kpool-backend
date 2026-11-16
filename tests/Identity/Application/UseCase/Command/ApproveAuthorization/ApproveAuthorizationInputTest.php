<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\ApproveAuthorization;

use Illuminate\Support\Str;
use Source\Identity\Application\UseCase\Command\ApproveAuthorization\ApproveAuthorizationInput;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class ApproveAuthorizationInputTest extends TestCase
{
    public function testContract(): void
    {
        $identity = new IdentityIdentifier((string) Str::uuid7());
        $input = new ApproveAuthorizationInput('request', $identity, [AuthorizationScope::DraftWrite]);
        $this->assertSame('request', $input->requestIdentifier());
        $this->assertSame($identity, $input->identityIdentifier());
        $this->assertSame([AuthorizationScope::DraftWrite], $input->scopes());
    }
}
