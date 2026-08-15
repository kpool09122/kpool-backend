<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\UpdatePrincipalGroupMembers;

use Application\Http\Context\AccountContext;
use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Wiki\Principal\Application\Exception\CannotRemoveLastWikiAdministratorException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInterface;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class UpdatePrincipalGroupMembersAction
{
    public function __construct(
        private UpdatePrincipalGroupMembersInterface $updatePrincipalGroupMembers,
        private AccountContext $accountContext,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(UpdatePrincipalGroupMembersRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new UpdatePrincipalGroupMembersInput(
                    accountIdentifier: $this->accountContext->principal()->accountIdentifier(),
                    operatorPrincipalIdentifier: $this->wikiContext->principalIdentifier,
                    principalGroups: array_map(static fn (array $principalGroup): PrincipalGroupMembers => new PrincipalGroupMembers(
                        new PrincipalGroupIdentifier($principalGroup['principalGroupIdentifier']),
                        array_map(static fn (string $principalIdentifier): PrincipalIdentifier => new PrincipalIdentifier($principalIdentifier), $principalGroup['principalIdentifiers']),
                    ), $request->principalGroups()),
                    accountType: $this->accountContext->accountType(),
                );
                $output = new UpdatePrincipalGroupMembersOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->updatePrincipalGroupMembers->process($input, $output);
                DB::commit();
            } catch (AccountUpdateForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('account_update_forbidden', $language), previous: $e);
            } catch (PrincipalGroupNotFoundException|PrincipalNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message($e instanceof PrincipalGroupNotFoundException ? 'principal_group_not_found' : 'principal_not_found', $language), previous: $e);
            } catch (CannotRemoveLastWikiAdministratorException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('cannot_remove_last_wiki_administrator', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
