<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreatePasskeyOptions;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyOptions\CreatePasskeyOptionsInput;
use Source\Identity\Application\UseCase\Command\CreatePasskeyOptions\CreatePasskeyOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyOptions\CreatePasskeyOptionsOutput;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreatePasskeyOptionsAction
{
    public function __construct(
        private CreatePasskeyOptionsInterface $createPasskeyOptions,
        private ActorContext $actorContext,
        // 防御的catch内でのみ利用され、PHPStanのchecked exception解析では未到達扱いになるため。
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $output = new CreatePasskeyOptionsOutput();
            $this->createPasskeyOptions->process(new CreatePasskeyOptionsInput(
                $this->actorContext->identityIdentifier,
            ), $output);
        } catch (IdentityNotFoundException $exception) {
            $this->logger->error((string) $exception);
            $httpException = new NotFoundHttpException(
                detail: error_message('identity_not_found', $this->actorContext->language->value),
                previous: $exception,
            );

            return response()->json($httpException->toProblemDetails(), $httpException->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: $exception->getMessage(), previous: $exception);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
