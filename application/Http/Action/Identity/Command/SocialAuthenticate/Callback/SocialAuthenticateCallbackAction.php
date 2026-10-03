<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SocialAuthenticate\Callback;

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
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialOAuthException;
use Source\Identity\Domain\Exception\StepUpSocialAuthenticationFailedException;
use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Throwable;

readonly class SocialAuthenticateCallbackAction
{
    public function __construct(
        private SocialLoginCallbackInterface $socialLoginCallback,
        private CompletePasskeyRecoveryWithSocialInterface $completePasskeyRecoveryWithSocial,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SocialAuthenticateCallbackRequest $request
     * @return JsonResponse|RedirectResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SocialAuthenticateCallbackRequest $request): JsonResponse|RedirectResponse
    {
        try {
            try {
                $provider = SocialProvider::fromString($request->provider());
                $code = new OAuthCode($request->code());
                $state = new OAuthState($request->state(), new DateTimeImmutable('+10 minutes'));
                $isPasskeyRecovery = str_starts_with((string) $state, 'passkey-recovery-');
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            $language = $request->language();
            $output = $isPasskeyRecovery
                ? $this->processPasskeyRecoveryCallback($provider, $code, $state, $language)
                : $this->processSocialLoginCallback($provider, $code, $state, $language);
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return redirect()->away(ReturnToUrl::toFrontendUrl($output->redirectUrl()));
    }

    /** @throws UnprocessableEntityHttpException */
    private function processSocialLoginCallback(
        SocialProvider $provider,
        OAuthCode $code,
        OAuthState $state,
        string $language,
    ): SocialLoginCallbackOutput {
        $input = new SocialLoginCallbackInput($provider, $code, $state);
        $output = new SocialLoginCallbackOutput();

        DB::beginTransaction();

        try {
            $this->socialLoginCallback->process($input, $output);
            DB::commit();
        } catch (InvalidOAuthStateException $e) {
            DB::rollBack();

            throw new UnprocessableEntityHttpException(detail: error_message('invalid_oauth_state', $language), previous: $e);
        } catch (SocialOAuthException $e) {
            DB::rollBack();

            throw new UnprocessableEntityHttpException(detail: error_message('social_oauth_error', $language), previous: $e);
        } catch (SocialLinkingSessionInvalidException $e) {
            DB::rollBack();

            throw new UnprocessableEntityHttpException(detail: error_message('invalid_social_linking', $language), previous: $e);
        } catch (StepUpSocialAuthenticationFailedException $e) {
            DB::rollBack();

            throw new UnprocessableEntityHttpException(detail: 'Social step-up authentication failed.', previous: $e);
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $output;
    }

    /** @throws UnprocessableEntityHttpException */
    private function processPasskeyRecoveryCallback(
        SocialProvider $provider,
        OAuthCode $code,
        OAuthState $state,
        string $language,
    ): CompletePasskeyRecoveryWithSocialOutput {
        $input = new CompletePasskeyRecoveryWithSocialInput($provider, $code, $state);
        $output = new CompletePasskeyRecoveryWithSocialOutput();

        DB::beginTransaction();

        try {
            $this->completePasskeyRecoveryWithSocial->process($input, $output);
            DB::commit();
        } catch (InvalidOAuthStateException $e) {
            DB::rollBack();

            throw new UnprocessableEntityHttpException(detail: error_message('invalid_oauth_state', $language), previous: $e);
        } catch (SocialOAuthException|PasskeyRecoveryVerificationFailedException $e) {
            DB::rollBack();

            throw new UnprocessableEntityHttpException(
                detail: error_message('social_oauth_error', $language),
                previous: $e,
            );
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $output;
    }
}
