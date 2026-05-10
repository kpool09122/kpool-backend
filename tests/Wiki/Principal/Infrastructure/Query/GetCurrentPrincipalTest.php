<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Query;

use Illuminate\Contracts\Container\BindingResolutionException;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInput;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetCurrentPrincipalTest extends TestCase
{
    /**
     * @throws PrincipalNotFoundException
     * @throws BindingResolutionException
     * @throws JsonException
     */
    #[Group('useDb')]
    public function testProcessReturnsCurrentPrincipal(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principalIdentifier, $identityIdentifier);

        $useCase = $this->app->make(GetCurrentPrincipalInterface::class);
        $readModel = $useCase->process(new GetCurrentPrincipalInput($identityIdentifier));

        $result = $readModel->toArray();
        $this->assertSame((string) $principalIdentifier, $result['principalIdentifier']);
        $this->assertSame((string) $identityIdentifier, $result['identityIdentifier']);
        $this->assertFalse($result['isDelegatedPrincipal']);
        $this->assertTrue($result['isEnabled']);
    }

    /**
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testProcessThrowsExceptionWhenPrincipalDoesNotExist(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $this->expectException(PrincipalNotFoundException::class);

        $useCase = $this->app->make(GetCurrentPrincipalInterface::class);
        $useCase->process(new GetCurrentPrincipalInput($identityIdentifier));
    }
}
