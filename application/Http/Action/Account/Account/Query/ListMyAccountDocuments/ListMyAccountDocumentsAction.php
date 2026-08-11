<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\ListMyAccountDocuments;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountDocumentListForbiddenException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsInput;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsInterface;
use Source\Account\Account\Application\UseCase\Query\ListAccountDocuments\ListAccountDocumentsOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListMyAccountDocumentsAction
{
    public function __construct(
        private ListAccountDocumentsInterface $listAccountDocuments,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ListMyAccountDocumentsRequest $request): JsonResponse
    {
        try {
            $language = $request->language();
            $principal = $this->accountContext->principal();

            $input = new ListAccountDocumentsInput(
                accountIdentifier: $principal->accountIdentifier(),
                principal: $principal,
            );

            try {
                $output = new ListAccountDocumentsOutput();
                $this->listAccountDocuments->process($input, $output);
            } catch (AccountNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('account_not_found', $language), previous: $e);
            } catch (AccountDocumentListForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            }
        } catch (ForbiddenHttpException|NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
