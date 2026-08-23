<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\OfficialCertification\Command\SyncOwnedWikiCertifications;

use Application\Http\Context\AccountContext;
use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertificationsInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertificationsInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications\SyncOwnedWikiCertificationsOutput;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SyncOwnedWikiCertificationsAction
{
    public function __construct(
        private SyncOwnedWikiCertificationsInterface $syncOwnedWikiCertifications,
        private AccountContext $accountContext,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(SyncOwnedWikiCertificationsRequest $request): JsonResponse
    {
        try {
            try {
                $input = new SyncOwnedWikiCertificationsInput(
                    $this->accountContext->principal()->accountIdentifier(),
                    $this->accountContext->accountCategory(),
                    $this->wikiContext->principalIdentifier,
                    array_map(
                        static fn (string $id): TranslationSetIdentifier => new TranslationSetIdentifier($id),
                        $request->translationSetIdentifiers(),
                    ),
                );
                $output = new SyncOwnedWikiCertificationsOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->syncOwnedWikiCertifications->process($input, $output);
                DB::commit();
            } catch (DisallowedException|PrincipalNotFoundException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
