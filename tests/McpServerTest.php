<?php

declare(strict_types=1);

namespace Tests;

use App\McpServer;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class McpServerTest extends TestCase
{
    private McpServer $server;

    protected function setUp(): void
    {
        $this->server = new McpServer('test-server', '1.0.0');
    }

    /**
     * Test that McpServer correctly invokes a registered tool's handler and returns its result.
     */
    public function testToolCallInvokesHandlerAndReturnsResult(): void
    {
        // Register a tool with a simple handler
        $this->server->addTool(
            'greet',
            'Returns a greeting message',
            [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
                'required' => ['name'],
            ],
            fn(array $args) => "Hello, {$args['name']}!"
        );

        // Use reflection to call the private handleRequest method
        $handleRequest = new ReflectionMethod(McpServer::class, 'handleRequest');

        $request = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'greet',
                'arguments' => ['name' => 'World'],
            ],
        ];

        $response = $handleRequest->invoke($this->server, $request);

        $this->assertSame('2.0', $response['jsonrpc']);
        $this->assertSame(1, $response['id']);
        $this->assertArrayHasKey('result', $response);
        $this->assertArrayNotHasKey('error', $response);
        $this->assertSame('text', $response['result']['content'][0]['type']);
        $this->assertSame('Hello, World!', $response['result']['content'][0]['text']);
    }

    /**
     * Test that McpServer returns an error response when attempting to call an unregistered tool.
     */
    public function testToolCallReturnsErrorForUnregisteredTool(): void
    {
        // Use reflection to call the private handleRequest method
        $handleRequest = new ReflectionMethod(McpServer::class, 'handleRequest');

        $request = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nonexistent_tool',
                'arguments' => [],
            ],
        ];

        $response = $handleRequest->invoke($this->server, $request);

        $this->assertSame('2.0', $response['jsonrpc']);
        $this->assertSame(2, $response['id']);
        $this->assertArrayHasKey('error', $response);
        $this->assertArrayNotHasKey('result', $response);
        $this->assertSame(-32602, $response['error']['code']);
        $this->assertStringContainsString('Unknown tool', $response['error']['message']);
        $this->assertStringContainsString('nonexistent_tool', $response['error']['message']);
    }

    /**
     * Test that tool handler returning array gets JSON encoded.
     */
    public function testToolCallJsonEncodesArrayResult(): void
    {
        $this->server->addTool(
            'get_data',
            'Returns structured data',
            ['type' => 'object', 'properties' => []],
            fn(array $args) => ['key' => 'value', 'number' => 42]
        );

        $handleRequest = new ReflectionMethod(McpServer::class, 'handleRequest');

        $request = [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get_data',
                'arguments' => [],
            ],
        ];

        $response = $handleRequest->invoke($this->server, $request);

        $this->assertArrayHasKey('result', $response);
        $resultText = $response['result']['content'][0]['text'];
        $decoded = json_decode($resultText, true);
        $this->assertSame(['key' => 'value', 'number' => 42], $decoded);
    }

    /**
     * Test that tool handler exceptions are caught and returned as error content.
     */
    public function testToolCallHandlesExceptionsGracefully(): void
    {
        $this->server->addTool(
            'failing_tool',
            'A tool that always throws',
            ['type' => 'object', 'properties' => []],
            fn(array $args) => throw new \RuntimeException('Something went wrong')
        );

        $handleRequest = new ReflectionMethod(McpServer::class, 'handleRequest');

        $request = [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => [
                'name' => 'failing_tool',
                'arguments' => [],
            ],
        ];

        $response = $handleRequest->invoke($this->server, $request);

        $this->assertArrayHasKey('result', $response);
        $this->assertTrue($response['result']['isError']);
        $this->assertStringContainsString('Something went wrong', $response['result']['content'][0]['text']);
    }
}
