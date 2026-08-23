<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Query\ListMyOfficialCertifications;

use Application\Http\Context\AccountContext;
use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications\ListMyOfficialCertificationsInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications\ListMyOfficialCertificationsInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications\ListMyOfficialCertificationsOutput;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListMyOfficialCertificationsAction
{
    public function __construct(
        private ListMyOfficialCertificationsInterface $listMyOfficialCertifications,
        private WikiContext $wikiContext,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListMyOfficialCertificationsRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListMyOfficialCertificationsInput(
                    principalIdentifier: $this->wikiContext->principalIdentifier,
                    accountIdentifier: $this->accountContext->principal()->accountIdentifier(),
                    accountCategory: $this->accountContext->accountCategory(),
                    status: $request->status() !== null ? CertificationStatus::from($request->status()) : null,
                    perPage: $request->perPage(),
                );
                $output = new ListMyOfficialCertificationsOutput();
                $this->listMyOfficialCertifications->process($input, $output);
            } catch (DisallowedException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('principal_not_found', $request->language()), previous: $e);
            }
        } catch (NotFoundHttpException|ForbiddenHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
