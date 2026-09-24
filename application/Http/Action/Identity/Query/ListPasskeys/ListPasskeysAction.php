<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\ListPasskeys;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInput;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInterface;
use Source\Identity\Application\UseCase\Query\PasskeyReadModel;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListPasskeysAction
{
    public function __construct(
        private ListPasskeysInterface $listPasskeys,
        private ActorContext $actorContext,
        // 防御的catch内でのみ利用され、PHPStanのchecked exception解析では未到達扱いになるため。
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(): JsonResponse
    {
        try {
            $passkeys = $this->listPasskeys->process(
                new ListPasskeysInput($this->actorContext->identityIdentifier),
            );
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json([
            'passkeys' => array_map(
                static fn (PasskeyReadModel $passkey): array => $passkey->toArray(),
                $passkeys,
            ),
        ], Response::HTTP_OK);
    }
}
