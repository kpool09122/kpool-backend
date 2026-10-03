<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreatePasskeyAuthenticationOptions;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsInput;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions\CreatePasskeyAuthenticationOptionsOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreatePasskeyAuthenticationOptionsAction
{
    public function __construct(
        private CreatePasskeyAuthenticationOptionsInterface $createOptions,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $output = new CreatePasskeyAuthenticationOptionsOutput();
            $this->createOptions->process(new CreatePasskeyAuthenticationOptionsInput(), $output);
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: $exception->getMessage(), previous: $exception);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
