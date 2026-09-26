<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreateStepUpPasskeyOptions;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptionsInput;
use Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions\CreateStepUpPasskeyOptionsOutput;
use Source\Identity\Domain\Exception\PasskeyRecoveryRequiredException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreateStepUpPasskeyOptionsAction
{
    public function __construct(
        private CreateStepUpPasskeyOptionsInterface $useCase,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            try {
                $output = new CreateStepUpPasskeyOptionsOutput();
                $this->useCase->process(
                    new CreateStepUpPasskeyOptionsInput($this->actorContext->identityIdentifier),
                    $output,
                );
            } catch (PasskeyRecoveryRequiredException $exception) {
                throw new ConflictHttpException(
                    detail: 'No usable passkey is available. Use the dedicated passkey recovery flow.',
                    previous: $exception,
                );
            }
        } catch (ConflictHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: $exception->getMessage(), previous: $exception);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
