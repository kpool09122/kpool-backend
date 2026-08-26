<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Http\Action\Command\SubmitContact;

use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactOutput;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitContactActionTest extends TestCase
{
    public function testInvokeAssociatesContactWithAuthenticatedIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $contact = $this->contact($identityIdentifier);

        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('id')->once()->andReturn((string) $identityIdentifier);
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var SubmitContactInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(SubmitContactInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::on(static fn (mixed $input): bool => $input instanceof SubmitContactInput
                    && (string) $input->identityIdentifier() === (string) $identityIdentifier),
                Mockery::on(static function (mixed $output) use ($contact): bool {
                    if (! $output instanceof SubmitContactOutput) {
                        return false;
                    }

                    $output->setContact($contact);

                    return true;
                }),
            );

        $response = $this->action($useCase)($this->request());

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame((string) $identityIdentifier, $response->getData(true)['identityIdentifier']);
    }

    public function testInvokeKeepsContactAnonymousWhenUnauthenticated(): void
    {
        $contact = $this->contact(null);

        Auth::shouldReceive('check')->once()->andReturn(false);
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var SubmitContactInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(SubmitContactInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::on(static fn (mixed $input): bool => $input instanceof SubmitContactInput
                    && $input->identityIdentifier() === null),
                Mockery::on(static function (mixed $output) use ($contact): bool {
                    if (! $output instanceof SubmitContactOutput) {
                        return false;
                    }

                    $output->setContact($contact);

                    return true;
                }),
            );

        $response = $this->action($useCase)($this->request());

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertNull($response->getData(true)['identityIdentifier']);
    }

    private function action(SubmitContactInterface $useCase): SubmitContactAction
    {
        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        return new SubmitContactAction($useCase, $logger);
    }

    /** @return SubmitContactRequest&Mockery\MockInterface */
    private function request(): SubmitContactRequest
    {
        /** @var SubmitContactRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(SubmitContactRequest::class);
        $request->shouldReceive('category')->once()->andReturn(Category::SUGGESTIONS->value);
        $request->shouldReceive('name')->once()->andReturn('Test User');
        $request->shouldReceive('email')->once()->andReturn('test@example.com');
        $request->shouldReceive('content')->once()->andReturn('Test contact content');
        $request->shouldReceive('language')->once()->andReturn(Language::JAPANESE->value);

        return $request;
    }

    private function contact(?IdentityIdentifier $identityIdentifier): Contact
    {
        return new Contact(
            new ContactIdentifier(StrTestHelper::generateUuid()),
            $identityIdentifier,
            Category::SUGGESTIONS,
            new ContactName('Test User'),
            new Email('test@example.com'),
            new Content('Test contact content'),
            Language::JAPANESE,
        );
    }
}
