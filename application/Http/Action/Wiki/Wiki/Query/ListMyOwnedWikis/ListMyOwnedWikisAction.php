<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListMyOwnedWikis;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis\ListMyOwnedWikisOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListMyOwnedWikisAction
{
    public function __construct(
        private ListMyOwnedWikisInterface $listMyOwnedWikis,
        private AccountContext $accountContext,
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListMyOwnedWikisRequest $request): JsonResponse
    {
        try {
            $output = new ListMyOwnedWikisOutput();
            $this->listMyOwnedWikis->process(
                new ListMyOwnedWikisInput(
                    accountIdentifier: $this->accountContext->principal()->accountIdentifier(),
                    accountCategory: $this->accountContext->accountCategory(),
                    perPage: $request->perPage(),
                ),
                $output,
            );
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
