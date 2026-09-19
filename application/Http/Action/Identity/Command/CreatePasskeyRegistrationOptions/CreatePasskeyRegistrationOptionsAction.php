<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreatePasskeyRegistrationOptions;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsInput;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsInterface;
use Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions\CreatePasskeyRegistrationOptionsOutput;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeExpiredException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\InvalidSignupInvitationException;
use Source\Identity\Domain\Exception\UnauthorizedEmailException;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreatePasskeyRegistrationOptionsAction
{
    public function __construct(
        private CreatePasskeyRegistrationOptionsInterface $createOptions,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreatePasskeyRegistrationOptionsRequest $request): JsonResponse
    {
        try {
            try {
                $input = new CreatePasskeyRegistrationOptionsInput(
                    new Email($request->email()),
                    new SignupSession(
                        $request->accountType() !== null ? AccountType::from($request->accountType()) : null,
                        $request->oneTimeToken() !== null ? new OneTimeToken($request->oneTimeToken()) : null,
                        $request->returnTo(),
                    ),
                );
                $output = new CreatePasskeyRegistrationOptionsOutput();
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }

            $language = $request->language();

            try {
                $this->createOptions->process($input, $output);
            } catch (AuthCodeSessionNotFoundException $exception) {
                throw new NotFoundHttpException(
                    detail: error_message('auth_code_session_not_found', $language),
                    previous: $exception,
                );
            } catch (AlreadyUserExistsException $exception) {
                throw new ConflictHttpException(
                    detail: error_message('already_user_exists', $language),
                    previous: $exception,
                );
            } catch (UnauthorizedEmailException $exception) {
                throw new ForbiddenHttpException(
                    detail: error_message('unauthorized_email', $language),
                    previous: $exception,
                );
            } catch (AuthCodeExpiredException $exception) {
                throw new UnprocessableEntityHttpException(
                    detail: error_message('auth_code_expired', $language),
                    previous: $exception,
                );
            } catch (InvalidSignupInvitationException $exception) {
                throw new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception);
            }
        } catch (NotFoundHttpException|ConflictHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: $exception->getMessage(), previous: $exception);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
