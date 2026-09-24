<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\AddPasskeyOptions;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsInput;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsInterface;
use Source\Identity\Application\UseCase\Command\AddPasskeyOptions\AddPasskeyOptionsOutput;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidDelegationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class AddPasskeyOptionsAction
{
    public function __construct(
        private AddPasskeyOptionsInterface $addPasskeyOptions,
        private ActorContext $actorContext,
        // 防御的catch内でのみ利用され、PHPStanのchecked exception解析では未到達扱いになるため。
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $output = new AddPasskeyOptionsOutput();
            $this->addPasskeyOptions->process(new AddPasskeyOptionsInput(
                $this->actorContext->identityIdentifier,
                $this->actorContext->delegationIdentifier,
                $this->actorContext->originalIdentityIdentifier,
            ), $output);
        } catch (IdentityNotFoundException $exception) {
            $this->logger->error((string) $exception);
            $httpException = new NotFoundHttpException(
                detail: error_message('identity_not_found', $this->actorContext->language->value),
                previous: $exception,
            );

            return response()->json($httpException->toProblemDetails(), $httpException->getHttpStatus());
        } catch (InvalidDelegationException $exception) {
            $this->logger->error((string) $exception);
            $httpException = new ForbiddenHttpException(
                detail: error_message('invalid_delegation', $this->actorContext->language->value),
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
