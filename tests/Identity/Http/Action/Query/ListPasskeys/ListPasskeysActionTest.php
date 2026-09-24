<?php

declare(strict_types=1);

namespace Tests\Identity\Http\Action\Query\ListPasskeys;

use Application\Http\Action\Identity\Query\ListPasskeys\ListPasskeysAction;
use Application\Http\Context\ActorContext;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInput;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInterface;
use Source\Identity\Application\UseCase\Query\PasskeyReadModel;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ListPasskeysActionTest extends TestCase
{
    public function testInvokeReturnsPasskeysForActorIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $passkey = new PasskeyReadModel(
            '123e4567-e89b-72d3-a456-426614174001',
            'Security key',
            ['usb'],
            false,
            false,
            null,
            '2026-09-20T04:05:06+00:00',
        );
        /** @var ListPasskeysInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(ListPasskeysInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::on(static fn ($input): bool => $input instanceof ListPasskeysInput
                && $input->identityIdentifier() === $identityIdentifier))
            ->andReturn([$passkey]);
        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');
        $actorContext = new ActorContext($identityIdentifier, Language::JAPANESE, null, null);

        $response = (new ListPasskeysAction($useCase, $actorContext, $logger))();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(['passkeys' => [$passkey->toArray()]], $response->getData(true));
    }
}
