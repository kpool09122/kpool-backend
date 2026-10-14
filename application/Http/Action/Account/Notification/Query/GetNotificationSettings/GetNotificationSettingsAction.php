<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Notification\Query\GetNotificationSettings;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInput;
use Source\Account\Notification\Application\UseCase\Query\GetNotificationSettings\GetNotificationSettingsInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetNotificationSettingsAction
{
    public function __construct(
        private GetNotificationSettingsInterface $getNotificationSettings,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param GetNotificationSettingsRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(GetNotificationSettingsRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetNotificationSettingsInput(
                    accountIdentifier: new AccountIdentifier($request->accountId()),
                    principal: $this->accountContext->principal(),
                    accountType: $this->accountContext->accountType(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_notification_settings', $language), previous: $e);
            }

            try {
                $account = $this->getNotificationSettings->process($input);
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

        return response()->json($account->toArray(), Response::HTTP_OK);
    }
}
