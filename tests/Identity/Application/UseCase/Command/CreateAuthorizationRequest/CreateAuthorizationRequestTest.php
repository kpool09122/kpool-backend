<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Str;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest\CreateAuthorizationRequest;
use Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest\CreateAuthorizationRequestInput;
use Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest\CreateAuthorizationRequestOutput;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Service\AuthorizationRequestStorageService;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class CreateAuthorizationRequestTest extends TestCase
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
        $identity = new IdentityIdentifier((string) Str::uuid7());
        $input = new CreateAuthorizationRequestInput(
            $identity,
            $parameters->clientIdentifier,
            $parameters->redirectUri,
            $parameters->requestedScopes,
            $parameters->resource,
            $parameters->codeChallenge,
            $parameters->state,
        );
        /** @var AuthorizationRequestValidatorInterface&MockInterface $validator */
        $validator = Mockery::mock(AuthorizationRequestValidatorInterface::class);
        $validator->shouldReceive('validate')->once()->with(self::equalTo($parameters))->andReturn('Client');

        $output = new CreateAuthorizationRequestOutput();
        (new CreateAuthorizationRequest($validator, $storage))->process($input, $output);
        $result = $output->toArray();
        if (! isset($result['requestIdentifier'])) {
            throw new LogicException('Missing request identifier.');
        }
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $result['requestIdentifier']);
        $pending = $storage->requireValid($result['requestIdentifier'], $identity);
        $this->assertEquals($parameters, $pending->parameters);
        $this->assertEquals($identity, $pending->identityIdentifier);
    }
}
