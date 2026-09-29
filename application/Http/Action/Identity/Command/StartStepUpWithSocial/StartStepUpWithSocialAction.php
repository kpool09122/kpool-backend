<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\StartStepUpWithSocial;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\StartStepUpWithSocial\StartStepUpWithSocialInput;
use Source\Identity\Application\UseCase\Command\StartStepUpWithSocial\StartStepUpWithSocialInterface;
use Source\Identity\Application\UseCase\Command\StartStepUpWithSocial\StartStepUpWithSocialOutput;
use Source\Identity\Domain\Exception\StepUpSocialAuthenticationFailedException;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class StartStepUpWithSocialAction
{
    public function __construct(
        private StartStepUpWithSocialInterface $useCase,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $provider): JsonResponse
    {
        try {
            try {
                $input = new StartStepUpWithSocialInput(
                    $this->actorContext->identityIdentifier,
                    SocialProvider::fromString($provider),
                );
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }
            $output = new StartStepUpWithSocialOutput();

            try {
                $this->useCase->process($input, $output);
            } catch (StepUpSocialAuthenticationFailedException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }
        } catch (UnprocessableEntityHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: $exception->getMessage(), previous: $exception);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
