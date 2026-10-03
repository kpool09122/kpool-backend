<?php

declare(strict_types=1);

namespace Tests\Http\Action\Identity;

use Application\Http\Action\Identity\Command\StartStepUpWithSocial\StartStepUpWithSocialRequest;
use Application\Http\Action\Identity\Query\ListPasskeys\ListPasskeysAction;
use Application\Http\Context\ActorContext;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class RecentAuthenticationContractTest extends TestCase
{
    public function testMissingRecentAuthenticationHasAMachineReadableIdentifier(): void
    {
        /** @var ListPasskeysInterface&MockInterface $listPasskeys */
        $listPasskeys = Mockery::mock(ListPasskeysInterface::class);
        $listPasskeys->shouldReceive('process')->once()->andThrow(new StepUpAuthenticationRequiredException());
        $action = new ListPasskeysAction($listPasskeys, new ActorContext(
            new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001'),
            Language::ENGLISH,
        ), new NullLogger());

        $response = $action();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('"code":"recent_authentication_required"', (string) $response->getContent());
    }

    public function testErrorMessagesExistInEveryLanguageCatalogue(): void
    {
        foreach (['en', 'es', 'ja', 'ko', 'zh_CN', 'zh_TW'] as $language) {
            foreach (['identity_name_confirmation_mismatch', 'recent_authentication_required', 'identity_withdrawal_not_allowed', 'csrf_token_mismatch', 'unauthorized'] as $key) {
                $message = error_message($key, $language);
                $this->assertNotSame('errors.' . $key, $message);
                $this->assertNotSame('', $message);
            }
        }
    }

    public function testOnlyAllowlistedDestinationsAreAccepted(): void
    {
        $rules = (new StartStepUpWithSocialRequest())->rules();
        foreach (['passkeys', 'withdrawal'] as $destination) {
            $this->assertTrue(Validator::make(['returnTo' => $destination], $rules)->passes());
        }
        foreach ([null, '', 'https://evil.example', '//evil.example', '/settings/passkeys'] as $destination) {
            $this->assertTrue(Validator::make(['returnTo' => $destination], $rules)->fails());
        }
    }
}
