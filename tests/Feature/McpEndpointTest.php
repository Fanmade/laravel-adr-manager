<?php

declare(strict_types=1);

use Fanmade\AdrManager\Contracts\AdrRepository;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->app['env'] = 'local';
    config()->set('adr-manager.path', 'docs/adrs');
    app()->forgetInstance(AdrRepository::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory(base_path('docs'));
});

function rpc(string $method, array $params = [], int|string|null $id = 1): array
{
    $payload = ['jsonrpc' => '2.0', 'method' => $method, 'params' => $params];

    if ($id !== null) {
        $payload['id'] = $id;
    }

    return $payload;
}

it('responds to initialize with protocol and server info', function () {
    $this->postJson('/api/adr/mcp', rpc('initialize'))
        ->assertOk()
        ->assertJsonPath('jsonrpc', '2.0')
        ->assertJsonPath('id', 1)
        ->assertJsonPath('result.serverInfo.name', 'laravel-adr-manager')
        ->assertJsonStructure(['result' => ['protocolVersion', 'capabilities', 'serverInfo']]);
});

it('lists the available tools', function () {
    $this->postJson('/api/adr/mcp', rpc('tools/list'))
        ->assertOk()
        ->assertJsonPath('result.tools.0.name', 'list_adrs')
        ->assertJsonPath('result.tools.1.name', 'get_adr_context');
});

it('calls list_adrs and returns the records as text content', function () {
    app(AdrRepository::class)->save(record('0001', 'First decision', 'accepted'));

    $response = $this->postJson('/api/adr/mcp', rpc('tools/call', [
        'name' => 'list_adrs',
    ]))->assertOk();

    expect($response->json('result.content.0.type'))->toBe('text')
        ->and($response->json('result.content.0.text'))->toContain('0001')
        ->and($response->json('result.content.0.text'))->toContain('First decision');
});

it('calls get_adr_context and returns the full record', function () {
    app(AdrRepository::class)->save(record('0007', 'Use PostgreSQL', 'accepted'));

    $response = $this->postJson('/api/adr/mcp', rpc('tools/call', [
        'name' => 'get_adr_context',
        'arguments' => ['id' => '0007'],
    ]))->assertOk();

    expect($response->json('result.content.0.text'))->toContain('Use PostgreSQL');
});

it('flags a get_adr_context miss as a tool error, not a protocol error', function () {
    $this->postJson('/api/adr/mcp', rpc('tools/call', [
        'name' => 'get_adr_context',
        'arguments' => ['id' => '9999'],
    ]))
        ->assertOk()
        ->assertJsonPath('result.isError', true);
});

it('returns a JSON-RPC error for an unknown method', function () {
    $this->postJson('/api/adr/mcp', rpc('does/not/exist'))
        ->assertOk()
        ->assertJsonPath('error.code', -32601);
});

it('returns a JSON-RPC error for an unknown tool', function () {
    $this->postJson('/api/adr/mcp', rpc('tools/call', ['name' => 'nope']))
        ->assertOk()
        ->assertJsonPath('error.code', -32602);
});

it('rejects a malformed envelope', function () {
    $this->postJson('/api/adr/mcp', ['jsonrpc' => '1.0', 'method' => 'ping', 'id' => 5])
        ->assertOk()
        ->assertJsonPath('error.code', -32600)
        ->assertJsonPath('id', 5);
});

it('accepts a notification without returning a body', function () {
    $this->call('POST', '/api/adr/mcp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], json_encode(rpc('notifications/initialized', [], null)))
        ->assertNoContent(202);
});

it('returns a parse error for invalid JSON', function () {
    $this->call('POST', '/api/adr/mcp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], '{ not valid json ]')
        ->assertOk()
        ->assertJsonPath('error.code', -32700);
});

it('returns a parse error when the body is not a JSON object or array', function () {
    $this->call('POST', '/api/adr/mcp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], '42')
        ->assertOk()
        ->assertJsonPath('error.code', -32700);
});

it('rejects a tools/call without a tool name', function () {
    $this->postJson('/api/adr/mcp', rpc('tools/call', []))
        ->assertOk()
        ->assertJsonPath('error.code', -32602);
});

it('rejects get_adr_context without an id argument', function () {
    $this->postJson('/api/adr/mcp', rpc('tools/call', ['name' => 'get_adr_context']))
        ->assertOk()
        ->assertJsonPath('error.code', -32602);
});

it('handles a JSON-RPC batch', function () {
    app(AdrRepository::class)->save(record('0001', 'First', 'accepted'));

    $response = $this->postJson('/api/adr/mcp', [
        rpc('ping', [], 1),
        rpc('tools/list', [], 2),
    ])->assertOk();

    expect($response->json())->toHaveCount(2);
});

it('requires authorization outside local', function () {
    $this->app['env'] = 'production';

    $this->postJson('/api/adr/mcp', rpc('ping'))->assertForbidden();
});
