<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\SiteManagement\Contact\Command\SubmitContact;

use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactRequest;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitContactActionTest extends TestCase
{
    public function testInvokeReturnsCreatedIdentifier(): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());

        /** @var SubmitContactRequest&MockInterface $request */
        $request = Mockery::mock(SubmitContactRequest::class);
        $request->shouldReceive('category')->once()->andReturn(Category::SUGGESTIONS->value);
        $request->shouldReceive('name')->once()->andReturn('問い合わせ太郎');
        $request->shouldReceive('email')->once()->andReturn('john.doe@example.com');
        $request->shouldReceive('content')->once()->andReturn('お問い合わせ内容');

        /** @var SubmitContactInterface&MockInterface $submitContact */
        $submitContact = Mockery::mock(SubmitContactInterface::class);
        $submitContact->shouldReceive('process')
            ->once()
            ->with(Mockery::on(static function (mixed $input): bool {
                return $input instanceof SubmitContactInput
                    && $input->identityIdentifier() === null
                    && $input->category() === Category::SUGGESTIONS
                    && (string) $input->name() === (string) new ContactName('問い合わせ太郎')
                    && (string) $input->email() === (string) new Email('john.doe@example.com')
                    && (string) $input->content() === (string) new Content('お問い合わせ内容');
            }))
            ->andReturn($contactIdentifier);

        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        $action = new SubmitContactAction($submitContact, $logger);
        $response = $action($request);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame([
            'id' => (string) $contactIdentifier,
        ], $response->getData(true));
    }
}
