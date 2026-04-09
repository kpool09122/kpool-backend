<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SocialLogin\Callback;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInput;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Callback\SocialLoginCallbackOutput;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Exception\SocialOAuthException;
use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SocialLoginCallbackAction
{
    public function __construct(
        private SocialLoginCallbackInterface $socialLoginCallback,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SocialLoginCallbackRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SocialLoginCallbackRequest $request): JsonResponse
    {
        try {
            try {
                $input = new SocialLoginCallbackInput(
                    provider: SocialProvider::fromString($request->provider()),
                    code: new OAuthCode($request->code()),
                    state: new OAuthState($request->state()),
                );
                $output = new SocialLoginCallbackOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->socialLoginCallback->process($input, $output);
                DB::commit();
            } catch (InvalidOAuthStateException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_oauth_state', $language), previous: $e);
            } catch (SocialOAuthException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('social_oauth_error', $language), previous: $e);
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

        return response()->json(['redirectUrl' => $output->redirectUrl()], Response::HTTP_OK);
    }
}
