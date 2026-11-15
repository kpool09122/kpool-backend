<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\DenyAuthorization;

use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Application\UseCase\Command\DenyAuthorization\DenyAuthorization;
use Source\Identity\Application\UseCase\Command\DenyAuthorization\DenyAuthorizationInput;
use Source\Identity\Application\UseCase\Command\DenyAuthorization\DenyAuthorizationOutput;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Service\AuthorizationRequestStorageService;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class DenyAuthorizationTest extends TestCase
{
    public function testProcessesServerOwnedRequest(): void
    {

        $request = Request::create('/');
        $session = new Store('oauth', new ArraySessionHandler(30));
        $session->start();
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        $storage = new AuthorizationRequestStorageService();
        $parameters = new AuthorizationCodeParameters(new OAuthClientIdentifier((string) Str::uuid7()), 'https://client.test/cb?existing=1', [AuthorizationScope::WikiRead], 'https://mcp.test', str_repeat('a', 43), 'state & value');
        /** @var AuthorizationRequestValidatorInterface&MockInterface $validator */
        $validator = Mockery::mock(AuthorizationRequestValidatorInterface::class);
        $validator->shouldReceive('validate')->once()->with($parameters)->andReturn('Client');

        $identity = new IdentityIdentifier((string) Str::uuid7());
        $pending = $storage->store($parameters, $identity);
        $output = new DenyAuthorizationOutput();
        (new DenyAuthorization($storage, $validator))->process(new DenyAuthorizationInput($pending->identifier, $identity), $output);
        $this->assertSame('https://client.test/cb?existing=1&error=access_denied&state=state%20%26%20value', $output->toArray()['redirectUri']);
        $this->expectException(AuthorizationRequestUnavailableException::class);
        $storage->requireValid($pending->identifier, $identity);
    }
}
