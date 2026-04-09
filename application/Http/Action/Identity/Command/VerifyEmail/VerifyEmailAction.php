<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\VerifyEmail;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailInput;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailInterface;
use Source\Identity\Application\UseCase\Command\VerifyEmail\VerifyEmailOutput;
use Source\Identity\Domain\Exception\AuthCodeExpiredException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\InvalidAuthCodeException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class VerifyEmailAction
{
    public function __construct(
        private VerifyEmailInterface $verifyEmail,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param VerifyEmailRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(VerifyEmailRequest $request): JsonResponse
    {
        try {
            try {
                $input = new VerifyEmailInput(
                    email: new Email($request->email()),
                    authCode: new AuthCode($request->authCode()),
                );
                $output = new VerifyEmailOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->verifyEmail->process($input, $output);
                DB::commit();
            } catch (AuthCodeSessionNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('auth_code_session_not_found', $language), previous: $e);
            } catch (AuthCodeExpiredException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('auth_code_expired', $language), previous: $e);
            } catch (InvalidAuthCodeException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_auth_code', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
