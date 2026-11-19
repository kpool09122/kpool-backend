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
    public function testManagedImageToolsUseThePublishedOpenApiContract(): void
    {
        $registry = app(OpenApiToolRegistry::class);
        $list = $registry->find('list_managed_wiki_images');
        $edit = $registry->find('update_wiki_image_metadata');
        self::assertNotNull($list);
        self::assertNotNull($edit);
        self::assertSame(['wiki-images:read'], $list->scopes);
        self::assertSame(['wiki-images:write'], $edit->scopes);
        self::assertStringContainsString('including hidden published images', OpenApiDocument::string($list->definition()['description']));
        self::assertStringContainsString('Preserves image file, approval and visibility', OpenApiDocument::string($edit->definition()['description']));
        $query = (object) ['status' => 'approved', 'page' => 2, 'perPage' => 100, 'license' => null, 'authorMissing' => true, 'licenseMissing' => false];
        self::assertTrue($list->accepts($query));
        self::assertSame(['path' => '/api/v1/wiki/managed-images', 'body' => null,
            'query' => ['status' => 'approved', 'page' => 2, 'perPage' => 100, 'license' => '', 'authorMissing' => 'true', 'licenseMissing' => 'false']], $list->request($query));
        foreach (['approved', 'pending', 'under_review', 'rejected'] as $status) {
            self::assertTrue($list->accepts((object) ['status' => $status, 'page' => null, 'perPage' => null, 'authorMissing' => null, 'licenseMissing' => null]));
        }
        foreach ([[], ['status' => 'unknown'], ['status' => 'approved', 'page' => 0], ['status' => 'approved', 'perPage' => 101], ['status' => 'approved', 'license' => 'unknown'], ['status' => 'approved', 'authorMissing' => 'true']] as $invalid) {
            self::assertFalse($list->accepts((object) $invalid));
        }
        $args = ['imageId' => '019a8fa0-3f20-7000-8000-000000000001', 'sourceUrl' => null, 'sourceName' => 'Source', 'author' => 'Author', 'license' => 'CC-BY-4.0', 'altText' => null, 'displayOrder' => 0];
        self::assertTrue($edit->accepts((object) $args));
        self::assertSame(['path' => '/api/v1/wiki/image/' . $args['imageId'] . '/metadata', 'body' => array_diff_key($args, ['imageId' => true]), 'query' => []], $edit->request((object) $args));
        foreach (array_keys($args) as $required) {
            $incomplete = $args;
            unset($incomplete[$required]);
            self::assertFalse($edit->accepts((object) $incomplete), $required);
        }
        foreach (['imageId' => 'invalid', 'sourceName' => '', 'author' => str_repeat('a', 256), 'license' => 'unknown', 'altText' => str_repeat('a', 513), 'sourceUrl' => str_repeat('a', 513), 'displayOrder' => -1, 'base64EncodedImage' => 'forbidden'] as $key => $value) {
            self::assertFalse($edit->accepts((object) array_replace($args, [$key => $value])), $key);
        }
        self::assertTrue($edit->accepts((object) array_replace($args, ['sourceUrl' => '', 'altText' => ''])));
    }

    public function testProductionToolsDeriveDescriptionsAndNullableUuidFromOpenApi(): void
    {
        $registry = app(OpenApiToolRegistry::class);
        self::assertSame(['get_account_context', 'switch_account'], array_slice(array_column($registry->definitions(), 'name'), 0, 2));
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
        self::assertSame([], OpenApiDocument::map($schema)['required']);
        self::assertArrayNotHasKey('serverId', OpenApiDocument::map(OpenApiDocument::map($schema)['properties']));
        self::assertSame(['type' => 'null'], data_get($schema, 'properties.value.anyOf.1'));
    }

    public function testComposedBodyKeepsInheritedRequiredFieldsAndRoutesThemToJson(): void
    {
        $document = $this->document();
        data_set($document, 'components.schemas.Input', ['allOf' => [
            ['type' => 'object', 'required' => ['title'], 'properties' => ['title' => ['type' => 'string', 'minLength' => 2]]],
            ['type' => 'object', 'required' => ['basic'], 'properties' => ['basic' => ['type' => 'object'], 'free' => []]],
        ]]);
        $tool = $this->registry(OpenApiDocument::map($document))->find('edit_item');
        self::assertNotNull($tool);
        $arguments = (object) ['id' => 'x', 'title' => 'ok', 'basic' => (object) ['name' => 'A'], 'free' => (object) []];
        self::assertTrue($tool->accepts($arguments));
        self::assertFalse($tool->accepts((object) ['id' => 'x', 'basic' => (object) []]));
        self::assertFalse($tool->accepts((object) ['id' => 'x', 'title' => 'x', 'basic' => (object) []]));
        self::assertFalse($tool->accepts((object) ['id' => 'x', 'title' => 'ok', 'basic' => (object) [], 'unknown' => 1]));
        self::assertSame(['title' => 'ok', 'basic' => $arguments->basic, 'free' => $arguments->free], $tool->request($arguments)['body']);
        self::assertStringContainsString('"free":{}', json_encode($tool->definition(), JSON_THROW_ON_ERROR));
    }

    public function testDraftListAndSubmitUseManagedHeadersAndArrayQueries(): void
    {
        $file = __DIR__ . '/../../doc/openapi/KPool.WikiApi.openapi.yaml';
        $registry = new OpenApiToolRegistry([
            'drafts' => ['document' => $file, 'method' => 'GET', 'path' => '/draft-wikis', 'scopes' => ['wiki-drafts:read']],
            'submit' => ['document' => $file, 'method' => 'POST', 'path' => '/wiki/{wikiId}/submit', 'scopes' => ['wiki-drafts:submit'],
                'managedHeaders' => ['X-Kpool-Visitor-Country', 'X-Kpool-Visitor-Region', 'X-Kpool-Visitor-Timestamp', 'X-Kpool-Visitor-Signature']],
        ]);
        $list = $registry->find('drafts');
        $submit = $registry->find('submit');
        self::assertNotNull($list);
        self::assertNotNull($submit);
        self::assertTrue($list->accepts((object) ['statuses' => ['pending', 'rejected']]));
        self::assertFalse($list->accepts((object) ['statuses' => ['bad']]));
        self::assertSame(['statuses' => 'pending,rejected', 'page' => 2], $list->request((object) ['statuses' => ['pending', 'rejected'], 'page' => 2])['query']);
        self::assertFalse($list->accepts((object) ['statuses' => []]));
        self::assertFalse($list->accepts((object) ['statuses' => ['pending'], 'page' => 0]));
        self::assertFalse($list->accepts((object) ['statuses' => ['pending'], 'perPage' => 101]));
        $args = (object) ['wikiId' => '019a8fa0-3f20-7000-8000-000000000001', 'resourceType' => 'group'];
        self::assertTrue($submit->accepts($args));
        self::assertFalse($submit->accepts((object) (get_object_vars($args) + ['X-Kpool-Visitor-Signature' => 'forged'])));
        self::assertStringNotContainsString('X-Kpool-Visitor', json_encode($submit->definition(), JSON_THROW_ON_ERROR));
        self::assertSame(['resourceType' => 'group'], $submit->request($args)['body']);
    }

    public function testBulkSubmissionUsesSubmitScopeAndOpenApiArrayConstraints(): void
    {
        $tool = app(OpenApiToolRegistry::class)->find('submit_wiki_drafts');
        self::assertNotNull($tool);
        self::assertSame(['wiki-drafts:submit'], $tool->scopes);
        self::assertSame([['type' => 'oauth2', 'scopes' => ['wiki-drafts:submit']]], $tool->definition()['securitySchemes']);
        $submission = (object) ['wikiId' => '019a8fa0-3f20-7000-8000-000000000001', 'resourceType' => 'group',
            'agencyIdentifier' => null, 'groupIdentifiers' => [], 'talentIdentifiers' => null];
        $arguments = (object) ['submissions' => [$submission]];
        self::assertTrue($tool->accepts($arguments));
        self::assertSame(['path' => '/api/v1/wiki/wiki/submit', 'body' => ['submissions' => [$submission]], 'query' => []], $tool->request($arguments));
        $submissions = array_map(static fn (int $index): stdClass => (object) [
            'wikiId' => sprintf('019a8fa0-3f20-7000-8000-%012d', $index), 'resourceType' => 'agency',
        ], range(1, 100));
        self::assertTrue($tool->accepts((object) ['submissions' => $submissions]));
        self::assertFalse($tool->accepts((object) ['submissions' => [...$submissions, $submission]]));
        self::assertFalse($tool->accepts((object) ['submissions' => []]));
        self::assertFalse($tool->accepts(new stdClass()));
        foreach ([['wikiId' => 'bad', 'resourceType' => 'group'], ['wikiId' => $submission->wikiId], ['wikiId' => $submission->wikiId, 'resourceType' => 'unknown']] as $invalid) {
            self::assertFalse($tool->accepts((object) ['submissions' => [(object) $invalid]]));
        }
        self::assertFalse($tool->accepts((object) ['submissions' => [$submission], 'X-Kpool-Visitor-Signature' => 'forged']));
        self::assertStringNotContainsString('X-Kpool-Visitor', json_encode($tool->definition(), JSON_THROW_ON_ERROR));
    }

    public function testAllWikiToolsAreRegisteredAndEditRejectsIncompleteReplacement(): void
    {
        $registry = app(OpenApiToolRegistry::class);
        self::assertCount(23, $registry->definitions());
        $create = $registry->find('create_wiki_draft');
        $edit = $registry->find('edit_wiki_draft');
        self::assertNotNull($create);
        self::assertNotNull($edit);
        self::assertTrue($create->accepts((object) ['resourceType' => 'agency', 'language' => 'ja', 'slug' => 'ag-test', 'basic' => (object) ['name' => 'Test']]));
        self::assertFalse($edit->accepts((object) ['wikiId' => '019a8fa0-3f20-7000-8000-000000000001', 'resourceType' => 'agency', 'basic' => (object) ['name' => 'Test']]));
        self::assertNull($registry->find('auto_create_wiki'));
        foreach (config()->array('mcp_tools') as $registration) {
            self::assertNotContains(data_get($registration, 'path'), ['/wiki/auto-create', '/wiki/{wikiId}/approve', '/wiki/{wikiId}/publish']);
        }
    }

    public function testCompleteAgencyEditPreservesEveryValueAndRejectsEachOmission(): void
    {
        $tool = app(OpenApiToolRegistry::class)->find('edit_wiki_draft');
        self::assertNotNull($tool);
        $basic = (object) ['name' => 'Agency', 'normalizedName' => 'agency', 'ceo' => 'CEO', 'normalizedCeo' => 'ceo',
            'foundedIn' => null, 'parentAgencyIdentifier' => null, 'status' => null, 'officialWebsite' => null, 'socialLinks' => ['https://example.test']];
        $args = (object) ['wikiId' => '019a8fa0-3f20-7000-8000-000000000001', 'resourceType' => 'agency', 'basic' => $basic,
            'sections' => [(object) ['type' => 'section', 'title' => 'History', 'displayOrder' => 0, 'contents' => [(object) ['type' => 'text', 'content' => 'Keep me']]]],
            'imageIdentifier' => null, 'themeColor' => '#abcdef', 'fontStyle' => null, 'title' => 'Title', 'metaDescription' => 'Description', 'keywords' => ['key'],
            'agencyIdentifier' => null, 'groupIdentifiers' => [], 'talentIdentifiers' => []];
        self::assertTrue($tool->accepts($args));
        $wrongType = clone $args;
        $wrongType->resourceType = 'group';
        self::assertFalse($tool->accepts($wrongType), 'A complete agency object must not erase group fields.');
        $expected = get_object_vars($args);
        unset($expected['wikiId']);
        self::assertSame($expected, $tool->request($args)['body']);
        foreach (array_keys(get_object_vars($args)) as $key) {
            $incomplete = clone $args;
            unset($incomplete->{$key});
            self::assertFalse($tool->accepts($incomplete), $key);
        }
        foreach (array_keys(get_object_vars($basic)) as $key) {
            $incomplete = clone $args;
            $incomplete->basic = clone $basic;
            unset($incomplete->basic->{$key});
            self::assertFalse($tool->accepts($incomplete), 'basic.' . $key);
        }
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
