<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\Logout;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\Logout\LogoutInput;
use Source\Identity\Application\UseCase\Command\Logout\LogoutInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class LogoutAction
{
    public function __construct(
        private LogoutInterface $logout,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param LogoutRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(LogoutRequest $request): JsonResponse
    {
        try {
            $input = new LogoutInput();

            DB::beginTransaction();

            try {
                $this->logout->process($input);
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json([], Response::HTTP_OK);
    }
}
