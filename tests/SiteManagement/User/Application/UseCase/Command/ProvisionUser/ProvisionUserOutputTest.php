<?php

declare(strict_types=1);

namespace Tests\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser\ProvisionUserOutput;
use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

class ProvisionUserOutputTest extends TestCase
{
    public function testSerializesSuppliedEntity(): void
    {
        $output = new ProvisionUserOutput();
        $entity = new User(new UserIdentifier('019c9b4c-0000-7000-8000-000000000001'), new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), Role::ADMIN);
        $output->setUser($entity);
        $this->assertSame([
            'userIdentifier' => (string) new UserIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            'identityIdentifier' => (string) new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            'role' => Role::ADMIN->value,
        ], $output->toArray());
    }

    public function testEmptyOutput(): void
    {
        $this->assertSame([], (new ProvisionUserOutput())->toArray());
    }
}
