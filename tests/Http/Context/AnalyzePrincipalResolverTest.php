<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\AnalyzePrincipalResolver;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\AnalyzePrincipalTestData;

class AnalyzePrincipalResolverTest extends TestCase
{
    public function testResolvesPrincipalForIdentityAndAccount(): void
    {
        $data = AnalyzePrincipalTestData::create();
        $principalRepository = $this->createMock(PrincipalRepositoryInterface::class);
        $principalRepository->expects(self::once())->method('findByIdentityIdentifierAndAccountIdentifier')->with($data->principal->identityIdentifier(), $data->principal->accountIdentifier())->willReturn($data->principal);

        self::assertSame($data->principal->principalIdentifier(), (new AnalyzePrincipalResolver($principalRepository))->resolve($data->principal->identityIdentifier(), $data->principal->accountIdentifier()));
    }

    public function testRejectsMissingPrincipal(): void
    {
        $data = AnalyzePrincipalTestData::create();
        $principalRepository = $this->createMock(PrincipalRepositoryInterface::class);
        $principalRepository->expects(self::once())->method('findByIdentityIdentifierAndAccountIdentifier')->with($data->principal->identityIdentifier(), $data->principal->accountIdentifier())->willReturn(null);

        $this->expectException(UnauthorizedException::class);
        (new AnalyzePrincipalResolver($principalRepository))->resolve($data->principal->identityIdentifier(), $data->principal->accountIdentifier());
    }
}
