<?php

declare(strict_types=1);

namespace Source\TestPresence\Application\UseCase\Command\Operation;

class OperationInput {}
class OperationOutput {}
interface OperationInputPort {}
interface OperationOutputPort {}
class Operation {}

namespace Source\TestPresence\Application\UseCase\Query;

class ExampleReadModel {}

namespace Source\TestPresence\Infrastructure\Factory;

class ExampleFactory {}
interface ExampleFactoryInterface {}
abstract class AbstractFactory {}

namespace Source\TestPresence\Domain\Service;

class Generator {}
interface GeneratorInterface {}
trait HelperTrait {}
abstract class AbstractService {}

namespace Source\TestPresence\Infrastructure\Repository;

class ExampleRepository {}
interface ExampleRepositoryInterface {}

namespace Source\TestPresence\Domain\ValueObject;

enum Action: string { case READ = 'read'; }
class CoveredValue {}
abstract class BaseValue {}

namespace Source\TestPresence\Domain\Entity;

class ExampleEntity {}

namespace Application\Http\Action\Example;

class ExampleAction {}
class ExampleRequest {}

namespace Kpool\PHPStan\Rules;

class ExampleService {}

namespace Source\TestPresence\Domain\Service;

function anonymousService(): object
{
    return new class {};
}
