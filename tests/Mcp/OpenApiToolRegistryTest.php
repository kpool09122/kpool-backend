<?php

declare(strict_types=1);

namespace Tests\Mcp;

use Application\Mcp\OpenApiDocument;
use Application\Mcp\OpenApiToolRegistry;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class OpenApiToolRegistryTest extends TestCase
{
    public function testProductionToolsDeriveDescriptionsAndNullableUuidFromOpenApi(): void
    {
        $registry = app(OpenApiToolRegistry::class);
        self::assertSame(['get_account_context', 'switch_account'], array_column($registry->definitions(), 'name'));
        $tool = $registry->find('switch_account');
        self::assertNotNull($tool);
        self::assertStringContainsString('without changing the authenticated identity', OpenApiDocument::string($tool->definition()['description']));
        self::assertTrue($tool->accepts((object) ['delegationIdentifier' => null]));
        self::assertTrue($tool->accepts((object) ['delegationIdentifier' => '019a8fa0-3f20-7000-8000-000000000001']));
        self::assertFalse($tool->accepts(new stdClass()));
        self::assertFalse($tool->accepts((object) ['delegationIdentifier' => 'not-uuid']));
        self::assertFalse($tool->accepts((object) ['delegationIdentifier' => null, 'url' => 'https://evil.test']));
        self::assertNull($registry->find('unregistered'));
    }

    public function testRegisteredEndpointDerivesInputConstraintsAndRequestRoutingWithoutTransportChanges(): void
    {
        $registry = $this->registry($this->document());
        $tool = $registry->find('edit_item');
        self::assertNotNull($tool);
        self::assertSame(['wiki:write'], $tool->scopes);
        $arguments = (object) ['id' => 'item/a', 'revision' => 2, 'title' => 'New title', 'labels' => ['a']];
        self::assertTrue($tool->accepts($arguments));
        self::assertSame(['path' => '/api/v1/wiki/items/item%2Fa', 'body' => ['title' => 'New title', 'labels' => ['a']], 'query' => ['revision' => 2]], $tool->request($arguments));
        foreach ([(object) ['id' => 'x', 'title' => ''], (object) ['id' => 'x', 'title' => 'ok', 'revision' => 0], (object) ['id' => 'x', 'title' => 'ok', 'labels' => [12]], (object) ['id' => 'x', 'title' => 'ok', 'secret' => 'x']] as $invalid) {
            self::assertFalse($tool->accepts($invalid));
        }
        $document = $this->document();
        data_set($document, 'components.schemas.Input.properties.title.minLength', 5);
        $changed = $this->registry(OpenApiDocument::map($document))->find('edit_item');
        self::assertNotNull($changed);
        self::assertFalse($changed->accepts((object) ['id' => 'x', 'title' => 'ok']));
    }

    public function testExistingWikiEndpointCanBeRegisteredWithNullableQueryAndEnumValidation(): void
    {
        $registry = new OpenApiToolRegistry(['list_wikis' => [
            'document' => __DIR__ . '/../../doc/openapi/KPool.WikiApi.openapi.yaml',
            'method' => 'GET', 'path' => '/wikis/{language}', 'scopes' => ['wiki:read'],
        ]]);
        $tool = $registry->find('list_wikis');
        self::assertNotNull($tool);
        $arguments = (object) ['language' => 'ja', 'sort' => null, 'perPage' => 10];
        self::assertTrue($tool->accepts($arguments));
        self::assertSame(['path' => '/api/v1/wiki/wikis/ja', 'body' => null, 'query' => ['sort' => '', 'perPage' => 10]], $tool->request($arguments));
        self::assertFalse($tool->accepts((object) ['language' => 'ja', 'sort' => 'unknown']));
        self::assertFalse($tool->accepts((object) ['language' => '..']));
        self::assertFalse($tool->accepts(new stdClass()));
    }

    /** @return iterable<string, array{string}> */
    public static function unsupportedDefinitions(): iterable
    {
        foreach (['missing_endpoint', 'external_ref', 'cycle', 'multipart', 'collision', 'cookie_auth', 'header', 'remote_server'] as $case) {
            yield $case => [$case];
        }
    }

    #[DataProvider('unsupportedDefinitions')]
    public function testInvalidRegistrationsFailClosed(string $case): void
    {
        $document = $this->document();
        switch ($case) {
            case 'missing_endpoint': $document['paths'] = [];

                break;
            case 'external_ref': data_set($document, 'components.schemas.Input', ['$ref' => 'https://evil.test/schema']);

                break;
            case 'cycle': data_set($document, 'components.schemas.Input', ['$ref' => '#/components/schemas/Input']);

                break;
            case 'multipart': data_set($document, 'paths./items/{id}.patch.requestBody.content', ['multipart/form-data' => []]);

                break;
            case 'collision': data_set($document, 'components.schemas.Input.properties.id', ['type' => 'string']);

                break;
            case 'cookie_auth': data_set($document, 'paths./items/{id}.patch.security', [['SessionCookieAuth' => []]]);

                break;
            case 'header': data_set($document, 'paths./items/{id}.parameters.0.in', 'header');

                break;
            case 'remote_server': $document['servers'] = [['url' => 'https://evil.test']];

                break;
        }
        $this->expectException(LogicException::class);
        $this->registry(OpenApiDocument::map($document));
    }

    public function testNullableCompositionAndReadOnlyInputsAreConverted(): void
    {
        $document = new OpenApiDocument(['openapi' => '3.0.0', 'components' => ['schemas' => ['Id' => ['type' => 'string', 'format' => 'uuid']]]]);
        $schema = $document->schema(['type' => 'object', 'required' => ['serverId'], 'properties' => [
            'serverId' => ['type' => 'string', 'readOnly' => true],
            'value' => ['type' => 'string', 'nullable' => true, 'allOf' => [['$ref' => '#/components/schemas/Id']]],
        ]]);
        self::assertSame([], $schema['required']);
        self::assertArrayNotHasKey('serverId', OpenApiDocument::map($schema['properties']));
        self::assertSame(['type' => 'null'], data_get($schema, 'properties.value.anyOf.1'));
    }

    /** @param array<string, mixed> $document */
    private function registry(array $document): OpenApiToolRegistry
    {
        $file = tempnam(sys_get_temp_dir(), 'mcp-openapi-');
        self::assertNotFalse($file);

        try {
            file_put_contents($file, Yaml::dump($document, 20));

            return new OpenApiToolRegistry(['edit_item' => ['document' => $file, 'method' => 'PATCH', 'path' => '/items/{id}', 'scopes' => ['wiki:write']]]);
        } finally {
            unlink($file);
        }
    }

    /** @return array<string, mixed> */
    private function document(): array
    {
        return ['openapi' => '3.0.0', 'servers' => [['url' => '/api/v1/wiki']],
            'paths' => ['/items/{id}' => ['parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]],
                'patch' => ['description' => 'Edit an item.', 'security' => [['InternalApiBearerAuth' => []]],
                    'parameters' => [['name' => 'revision', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]]],
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Input']]]]]]],
            'components' => ['schemas' => ['Input' => ['type' => 'object', 'required' => ['title'], 'properties' => [
                'title' => ['type' => 'string', 'minLength' => 1], 'labels' => ['type' => 'array', 'items' => ['type' => 'string']],
            ]]]]];
    }
}
