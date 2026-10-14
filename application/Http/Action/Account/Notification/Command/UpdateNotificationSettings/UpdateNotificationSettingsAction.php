<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Notification\Command\UpdateNotificationSettings;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsInput;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsInterface;
use Source\Account\Notification\Application\UseCase\Command\UpdateNotificationSettings\UpdateNotificationSettingsOutput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class UpdateNotificationSettingsAction
{
    public function __construct(
        private UpdateNotificationSettingsInterface $updateNotificationSettings,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param UpdateNotificationSettingsRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new UpdateNotificationSettingsInput(
                    accountIdentifier: new AccountIdentifier($request->accountId()),
                    principal: $this->accountContext->principal(),
                    identityIdentifiers: array_map(static fn (string $id): IdentityIdentifier => new IdentityIdentifier(strtolower($id)), $request->identityIdentifiers()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_notification_settings', $language), previous: $e);
            }

            try {
                $output = new UpdateNotificationSettingsOutput();
                DB::transaction(fn () => $this->updateNotificationSettings->process($input, $output));
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_notification_settings', $language), previous: $e);
            } catch (AccountNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('account_not_found', $language), previous: $e);
            } catch (AccountUpdateForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('account_update_forbidden', $language), previous: $e);
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
