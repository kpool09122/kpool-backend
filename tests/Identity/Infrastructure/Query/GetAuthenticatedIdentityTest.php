<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInput;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\TestCase;

class GetAuthenticatedIdentityTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsAuthenticatedIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5fe');
        CreateIdentity::create($identityIdentifier, [
            'username' => 'test-user',
            'email' => 'test@example.com',
            'language' => 'ja',
            'profile_image' => '/storage/profile/test.png',
        ]);

        $useCase = $this->app->make(GetAuthenticatedIdentityInterface::class);
        $readModel = $useCase->process(new GetAuthenticatedIdentityInput($identityIdentifier));

        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5fe', $readModel->identityIdentifier());
        $this->assertSame('test-user', $readModel->username());
        $this->assertSame('test@example.com', $readModel->email());
        $this->assertSame('ja', $readModel->language());
        $this->assertSame('/storage/profile/test.png', $readModel->profileImage());
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenIdentityDoesNotExist(): void
    {
        $useCase = $this->app->make(GetAuthenticatedIdentityInterface::class);

        $this->expectException(IdentityNotFoundException::class);

        $useCase->process(new GetAuthenticatedIdentityInput(
            new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5ff'),
        ));
    }
}
