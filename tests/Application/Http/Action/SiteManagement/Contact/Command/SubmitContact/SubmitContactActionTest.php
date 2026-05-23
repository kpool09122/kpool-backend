<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\SiteManagement\Contact\Command\SubmitContact;

use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactRequest;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitContactActionTest extends TestCase
{
    public function testInvokeReturnsCreatedIdentifier(): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());

        $request = Mockery::mock(SubmitContactRequest::class);
        $request->shouldReceive('category')->once()->andReturn(Category::SUGGESTIONS->value);
        $request->shouldReceive('name')->once()->andReturn('問い合わせ太郎');
        $request->shouldReceive('email')->once()->andReturn('john.doe@example.com');
        $request->shouldReceive('content')->once()->andReturn('お問い合わせ内容');

        $submitContact = Mockery::mock(SubmitContactInterface::class);
        $submitContact->shouldReceive('process')
            ->once()
            ->andReturn($contactIdentifier);

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
