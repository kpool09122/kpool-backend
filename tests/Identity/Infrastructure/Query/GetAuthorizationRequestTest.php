<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInput;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInterface;
use Tests\Helper\AuthorizationRequestScenario;
use Tests\TestCase;

#[Group('useDb')]
class GetAuthorizationRequestTest extends TestCase
{
    public function testReturnsRequestedScopesWithoutSelectingAnAccount(): void
    {
        $scenario = AuthorizationRequestScenario::create();
        $query = app(GetAuthorizationRequestInterface::class);
        $identity = $scenario->base->identityIdentifier();
        $input = new GetAuthorizationRequestInput($scenario->pending->identifier, $identity);
        $sessionBeforeRead = $scenario->request->session()->all();

        $result = $query->process($input)->toArray();

        $this->assertSame($sessionBeforeRead, $scenario->request->session()->all());
        $this->assertNull(app(CurrentAccountServiceInterface::class)->find($identity));
        $this->assertSame('ChatGPT', $result['clientName']);
        $this->assertContains(['scope' => 'wiki:read', 'description' => 'Wiki の閲覧'], $result['scopes']);
        $this->assertArrayNotHasKey('accounts', $result);
        $this->assertArrayNotHasKey('originalAccountIdentifier', $result);
    }
}
