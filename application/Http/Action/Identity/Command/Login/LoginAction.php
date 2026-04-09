<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\Login;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\Login\LoginInput;
use Source\Identity\Application\UseCase\Command\Login\LoginInterface;
use Source\Identity\Application\UseCase\Command\Login\LoginOutput;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidCredentialsException;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Shared\Domain\ValueObject\Email;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class LoginAction
{
    public function __construct(
        private LoginInterface $login,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param LoginRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            try {
                $input = new LoginInput(
                    email: new Email($request->email()),
                    password: new PlainPassword($request->password()),
                );
                $output = new LoginOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->login->process($input, $output);
                DB::commit();
            } catch (IdentityNotFoundException $e) {
                DB::rollBack();

                throw new UnauthorizedHttpException(detail: error_message('invalid_credentials', $language), previous: $e);
            } catch (InvalidCredentialsException $e) {
                DB::rollBack();

                throw new UnauthorizedHttpException(detail: error_message('invalid_credentials', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnauthorizedHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
