<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SendSocialLinkingEmail;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailInput;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailInterface;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailOutput;
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Shared\Domain\ValueObject\Language;
use Throwable;

readonly class SendSocialLinkingEmailAction
{
    public function __construct(
        private SendSocialLinkingEmailInterface $useCase,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(SendSocialLinkingEmailRequest $request): JsonResponse
    {
        try {
            try {
                $input = new SendSocialLinkingEmailInput(Language::from($request->language()));
                $output = new SendSocialLinkingEmailOutput();
                $this->useCase->process($input, $output);
            } catch (SocialLinkingSessionInvalidException|SocialLinkingVerificationFailedException|InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException(
                    detail: error_message('invalid_social_linking', $request->language()),
                    previous: $exception,
                );
            }
        } catch (UnprocessableEntityHttpException $exception) {
            $this->logger->error((string) $exception);

            return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(
                detail: error_message('internal_server_error', $request->language()),
                previous: $exception,
            );
        }

        return response()->json($output->toArray())->header('Cache-Control', 'no-store');
    }
}
