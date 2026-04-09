<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SendAuthCode;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInput;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SendAuthCodeAction
{
    public function __construct(
        private SendAuthCodeInterface $sendAuthCode,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SendAuthCodeRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SendAuthCodeRequest $request): JsonResponse
    {
        try {
            try {
                $input = new SendAuthCodeInput(
                    email: new Email($request->email()),
                    language: Language::from($request->language()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->sendAuthCode->process($input);
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json([], Response::HTTP_OK);
    }
}
