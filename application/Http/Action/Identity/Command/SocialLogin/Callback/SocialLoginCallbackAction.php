<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SocialLogin\Callback;

use Application\Http\Action\Identity\Support\ReturnToUrl;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial\CompletePasskeyRecoveryWithSocialInput;
use Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial\CompletePasskeyRecoveryWithSocialInterface;
use Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial\CompletePasskeyRecoveryWithSocialOutput;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInput;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackOutput;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\Exception\SocialOAuthException;
use Source\Identity\Domain\Exception\StepUpSocialAuthenticationFailedException;
use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Throwable;

readonly class SocialLoginCallbackAction
{
    public function __construct(
        private SocialLoginCallbackInterface $socialLoginCallback,
        private CompletePasskeyRecoveryWithSocialInterface $completePasskeyRecoveryWithSocial,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SocialLoginCallbackRequest $request
     * @return JsonResponse|RedirectResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SocialLoginCallbackRequest $request): JsonResponse|RedirectResponse
    {
        try {
            try {
                $provider = SocialProvider::fromString($request->provider());
                $code = new OAuthCode($request->code());
                $state = new OAuthState($request->state(), new DateTimeImmutable('+10 minutes'));
                $isPasskeyRecovery = str_starts_with((string) $state, 'passkey-recovery-');
                $input = $isPasskeyRecovery
                    ? new CompletePasskeyRecoveryWithSocialInput($provider, $code, $state)
                    : new SocialLoginCallbackInput($provider, $code, $state);
                $output = $isPasskeyRecovery
                    ? new CompletePasskeyRecoveryWithSocialOutput()
                    : new SocialLoginCallbackOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                if ($input instanceof CompletePasskeyRecoveryWithSocialInput) {
                    /** @var CompletePasskeyRecoveryWithSocialOutput $output */
                    $this->completePasskeyRecoveryWithSocial->process($input, $output);
                } else {
                    /** @var SocialLoginCallbackOutput $output */
                    $this->socialLoginCallback->process($input, $output);
                }
                DB::commit();
            } catch (InvalidOAuthStateException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_oauth_state', $language), previous: $e);
            } catch (SocialOAuthException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('social_oauth_error', $language), previous: $e);
            } catch (StepUpSocialAuthenticationFailedException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: 'Social step-up authentication failed.', previous: $e);
            } catch (PasskeyRecoveryVerificationFailedException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(
                    detail: error_message('social_oauth_error', $language),
                    previous: $e,
                );
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return redirect()->away(ReturnToUrl::toFrontendUrl($output->redirectUrl()));
    }
}
