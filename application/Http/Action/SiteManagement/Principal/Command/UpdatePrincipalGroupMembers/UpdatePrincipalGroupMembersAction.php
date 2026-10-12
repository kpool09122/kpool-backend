<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Principal\Command\UpdatePrincipalGroupMembers;

use Application\Http\Context\AccountContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Principal\Application\Exception\CannotRemoveLastAdministratorException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalNotFoundException;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class UpdatePrincipalGroupMembersAction
{
    public function __construct(
        private UpdatePrincipalGroupMembersInterface $updatePrincipalGroupMembers,
        private AccountContext $accountContext,
        private SiteManagementContext $siteManagementContext,
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
                    operatorPrincipalIdentifier: $this->siteManagementContext->principalIdentifier,
                    principalGroups: array_map(static fn (array $principalGroup): PrincipalGroupMembers => new PrincipalGroupMembers(
                        new PrincipalGroupIdentifier($principalGroup['principalGroupIdentifier']),
                        array_map(static fn (string $principalIdentifier): PrincipalIdentifier => new PrincipalIdentifier($principalIdentifier), $principalGroup['principalIdentifiers']),
                    ), $request->principalGroups()),
                );
                $output = new UpdatePrincipalGroupMembersOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_principal_group_members', $language), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->updatePrincipalGroupMembers->process($input, $output);
                $response = response()->json($output->toArray(), Response::HTTP_OK);
                DB::commit();
            } catch (UnauthorizedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('unauthorized', $language), previous: $e);
            } catch (PrincipalGroupNotFoundException|PrincipalNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message($e instanceof PrincipalGroupNotFoundException ? 'principal_group_not_found' : 'principal_not_found', $language), previous: $e);
            } catch (InvalidArgumentException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_principal_group_members', $language), previous: $e);
            } catch (CannotRemoveLastAdministratorException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('cannot_remove_last_site_management_administrator', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $request->language()), previous: $e);
        }

        return $response;
    }
}
