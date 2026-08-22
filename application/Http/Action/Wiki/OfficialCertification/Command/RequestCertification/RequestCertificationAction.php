<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Command\RequestCertification;

use Application\Http\Context\AccountContext;
use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationOutput;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RequestCertificationAction
{
    public function __construct(
        private RequestCertificationInterface $requestCertification,
        private AccountContext $accountContext,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RequestCertificationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RequestCertificationInput(
                    ResourceType::from($request->resourceType()),
                    new TranslationSetIdentifier($request->translationSetIdentifier()),
                    $this->accountContext->principal()->accountIdentifier(),
                    $this->wikiContext->principalIdentifier,
                );
                $output = new RequestCertificationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->requestCertification->process($input, $output);
                DB::commit();
            } catch (DisallowedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (OfficialCertificationAlreadyRequestedException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('official_certification_already_requested', $language), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
