<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Invitation\Command\CreateInvitation;

use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Invitation\Application\Exception\DisallowedInvitationException;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationInput;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationInterface;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationOutput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreateInvitationAction
{
    public function __construct(
        private CreateInvitationInterface $createInvitation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param CreateInvitationRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreateInvitationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new CreateInvitationInput(
                    accountIdentifier: new AccountIdentifier($request->accountIdentifier()),
                    inviterIdentityIdentifier: new IdentityIdentifier($request->inviterIdentityIdentifier()),
                    emails: array_map(
                        static fn (string $email) => new Email($email),
                        $request->emails()
                    ),
                );
                $output = new CreateInvitationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->createInvitation->process($input, $output);
                DB::commit();
            } catch (DisallowedInvitationException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed_invitation', $language), previous: $e);
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

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
