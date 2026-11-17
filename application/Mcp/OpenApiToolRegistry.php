<?php

declare(strict_types=1);

namespace Application\Mcp;

use LogicException;
use stdClass;

final class OpenApiToolRegistry
{
    /** @var array<string, OpenApiTool> */
    private array $tools = [];

    /** @param array<string, array<string, mixed>> $registrations */
    public function __construct(array $registrations)
    {
        $documents = [];
        foreach ($registrations as $name => $registration) {
            $file = OpenApiDocument::string($registration['document'] ?? null);
            $document = $documents[$file] ??= OpenApiDocument::fromFile($file);
            $this->tools[$name] = $this->compile($name, $registration, $document);
        }
    }

    /** @return list<array<string, mixed>> */
    public function definitions(): array
    {
        return array_values(array_map(static fn (OpenApiTool $tool): array => $tool->definition(), $this->tools));
    }

    public function find(string $name): ?OpenApiTool
    {
        return $this->tools[$name] ?? null;
    }

    /** @param array<string, mixed> $registration */
    private function compile(string $name, array $registration, OpenApiDocument $document): OpenApiTool
    {
        $method = strtoupper(OpenApiDocument::string($registration['method'] ?? null));
        $path = OpenApiDocument::string($registration['path'] ?? null);
        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)
            || ! preg_match('~\A/[a-zA-Z0-9_/{}/-]+\z~D', $path)
            || ! preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/D', $name)) {
            throw new LogicException('Invalid MCP endpoint registration.');
        }
        $operation = $document->operation($path, $method);
        if (isset($operation['servers'])) {
            throw new LogicException('Operation server overrides are not supported.');
        }
        $internalBearer = false;
        foreach (OpenApiDocument::items($operation['security'] ?? []) as $alternative) {
            $alternative = OpenApiDocument::map($alternative);
            if (array_keys($alternative) === ['InternalApiBearerAuth'] && $alternative['InternalApiBearerAuth'] === []) {
                $internalBearer = true;
            }
        }
        if (! $internalBearer) {
            throw new LogicException('MCP endpoints must support internal API Bearer authentication.');
        }
        $properties = $locations = $required = [];
        foreach (OpenApiDocument::items($operation['parameters']) as $parameter) {
            $parameter = $document->resolve(OpenApiDocument::map($parameter));
            $key = OpenApiDocument::string($parameter['name'] ?? null);
            $location = OpenApiDocument::string($parameter['in'] ?? null);
            if (! in_array($location, ['path', 'query'], true) || isset($properties[$key])
                || isset($parameter['content']) || ($parameter['allowReserved'] ?? false) !== false
                || ($parameter['style'] ?? ($location === 'path' ? 'simple' : 'form')) !== ($location === 'path' ? 'simple' : 'form')) {
                throw new LogicException('Unsupported or ambiguous MCP API parameter.');
            }
            $parameterSchema = $document->resolve(OpenApiDocument::map($parameter['schema'] ?? null));
            $schema = $document->schema($parameterSchema);
            if (! in_array($parameterSchema['type'] ?? null, ['string', 'integer', 'number', 'boolean'], true)) {
                throw new LogicException('Only scalar path/query parameters are supported.');
            }
            if ($location === 'path' && ((! in_array($parameterSchema['type'], ['string', 'integer'], true)) || ($parameterSchema['nullable'] ?? false) === true)) {
                throw new LogicException('Path parameters must be strings or integers.');
            }
            if ($location === 'path' && (! str_contains($path, '{' . $key . '}') || ($parameter['required'] ?? false) !== true)) {
                throw new LogicException('Path parameter must be present and required.');
            }
            if (isset($parameter['description'])) {
                $schema['description'] = OpenApiDocument::string($parameter['description']);
            }
            $properties[$key] = $schema;
            $locations[$key] = $location;
            if (($parameter['required'] ?? false) === true) {
                $required[] = $key;
            }
        }
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        foreach ($matches[1] as $key) {
            if (($locations[$key] ?? null) !== 'path') {
                throw new LogicException('Missing OpenAPI path parameter.');
            }
        }
        if (isset($operation['requestBody'])) {
            $body = $document->resolve(OpenApiDocument::map($operation['requestBody']));
            $content = OpenApiDocument::map($body['content'] ?? null);
            if (array_keys($content) !== ['application/json'] || ($body['required'] ?? false) !== true) {
                throw new LogicException('Only required JSON object request bodies are supported.');
            }
            $media = OpenApiDocument::map($content['application/json']);
            $schema = $document->schema(OpenApiDocument::map($media['schema'] ?? null));
            if (($schema['type'] ?? null) !== 'object' || isset($schema['allOf']) || isset($schema['anyOf']) || isset($schema['oneOf'])
                || array_diff(array_keys($schema), ['type', 'properties', 'required', 'description', 'title', 'additionalProperties']) !== []) {
                throw new LogicException('MCP request body must have explicit object properties.');
            }
            foreach (OpenApiDocument::map($schema['properties'] instanceof stdClass ? [] : $schema['properties']) as $key => $property) {
                if (isset($properties[$key])) {
                    throw new LogicException('MCP parameter/body names must not collide.');
                }
                $properties[$key] = $property;
                $locations[$key] = 'body';
            }
            foreach (OpenApiDocument::items($schema['required'] ?? []) as $key) {
                $required[] = OpenApiDocument::string($key);
            }
        }
        // MCP may require explicit selection even when omission is allowed by the HTTP API.
        foreach (OpenApiDocument::items($registration['requireExplicitArguments'] ?? []) as $key) {
            $key = OpenApiDocument::string($key);
            if (! isset($properties[$key])) {
                throw new LogicException('MCP required argument is absent from OpenAPI.');
            }
            $required[] = $key;
        }
        $scopes = array_map(OpenApiDocument::string(...), OpenApiDocument::items($registration['scopes'] ?? null));
        $annotations = OpenApiDocument::map($registration['annotations'] ?? []);
        foreach ($annotations as $value) {
            if (! is_bool($value)) {
                throw new LogicException('Invalid MCP annotation.');
            }
        }
        $description = OpenApiDocument::string($operation['description'] ?? $operation['summary'] ?? null);
        if (isset($registration['instructions'])) {
            $description .= "\n\n" . OpenApiDocument::string($registration['instructions']);
        }
        $projector = isset($registration['resultProjector']) ? OpenApiDocument::string($registration['resultProjector']) : null;
        if (! in_array($projector, [null, 'account_context', 'account_switched'], true)) {
            throw new LogicException('Unknown MCP result projector.');
        }

        return new OpenApiTool(
            $name,
            $method,
            $document->prefix() . $path,
            $description,
            ['type' => 'object', 'properties' => $properties === [] ? new stdClass() : $properties, 'required' => array_values(array_unique($required)), 'additionalProperties' => false],
            $locations,
            $scopes,
            $annotations,
            $projector,
            isset($operation['requestBody'])
        );
    }
}
