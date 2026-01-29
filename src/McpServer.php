<?php

declare(strict_types=1);

namespace App;

/**
 * A minimal MCP (Model Context Protocol) server implementation.
 * Communicates via JSON-RPC 2.0 over stdio.
 */
class McpServer
{
    private array $tools = [];
    private string $name;
    private string $version;

    public function __construct(string $name = 'mcp-server', string $version = '1.0.0')
    {
        $this->name = $name;
        $this->version = $version;
    }

    /**
     * Register a tool with the server.
     */
    public function addTool(string $name, string $description, array $inputSchema, callable $handler): void
    {
        $this->tools[$name] = [
            'name' => $name,
            'description' => $description,
            'inputSchema' => $inputSchema,
            'handler' => $handler,
        ];
    }

    /**
     * Run the server, reading from stdin and writing to stdout.
     */
    public function run(): void
    {
        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set streams to non-blocking would cause issues, keep blocking
        stream_set_blocking(STDIN, true);

        while (($line = fgets(STDIN)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $request = json_decode($line, true);
            if ($request === null) {
                $this->sendError(null, -32700, 'Parse error');
                continue;
            }

            $response = $this->handleRequest($request);
            if ($response !== null) {
                $this->sendResponse($response);
            }
        }
    }

    private function handleRequest(array $request): ?array
    {
        $id = $request['id'] ?? null;
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];

        // Handle notifications (no id) - no response needed
        if ($id === null && !str_starts_with($method, 'notifications/')) {
            return null;
        }

        return match ($method) {
            'initialize' => $this->handleInitialize($id, $params),
            'notifications/initialized' => null,
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolsCall($id, $params),
            'ping' => $this->handlePing($id),
            default => $this->makeError($id, -32601, "Method not found: {$method}"),
        };
    }

    private function handleInitialize(mixed $id, array $params): array
    {
        return $this->makeResult($id, [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => (object)[],
            ],
            'serverInfo' => [
                'name' => $this->name,
                'version' => $this->version,
            ],
        ]);
    }

    private function handleToolsList(mixed $id): array
    {
        $tools = [];
        foreach ($this->tools as $tool) {
            $tools[] = [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'inputSchema' => $tool['inputSchema'],
            ];
        }

        return $this->makeResult($id, ['tools' => $tools]);
    }

    private function handleToolsCall(mixed $id, array $params): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        if (!isset($this->tools[$toolName])) {
            return $this->makeError($id, -32602, "Unknown tool: {$toolName}");
        }

        try {
            $result = ($this->tools[$toolName]['handler'])($arguments);
            return $this->makeResult($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->makeResult($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Error: {$e->getMessage()}",
                    ],
                ],
                'isError' => true,
            ]);
        }
    }

    private function handlePing(mixed $id): array
    {
        return $this->makeResult($id, (object)[]);
    }

    private function makeResult(mixed $id, mixed $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    private function makeError(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    private function sendResponse(array $response): void
    {
        echo json_encode($response, JSON_UNESCAPED_SLASHES) . "\n";
        flush();
    }

    private function sendError(mixed $id, int $code, string $message): void
    {
        $this->sendResponse($this->makeError($id, $code, $message));
    }
}
