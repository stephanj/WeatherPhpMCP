# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A PHP implementation of a Model Context Protocol (MCP) server that provides weather information via the Open-Meteo API (no API key required).

## Requirements

- PHP 8.1+
- `allow_url_fopen` enabled in php.ini

## Commands

```bash
# Install dependencies
php composer.phar install

# Run the MCP server
php server.php

# Run tests
php phpunit.phar
```

## Architecture

The server implements MCP (Model Context Protocol) using JSON-RPC 2.0 over stdio:

- **server.php** - Entry point that bootstraps the server and registers tools
- **src/McpServer.php** - Generic MCP protocol handler (JSON-RPC dispatcher, tool registration, request/response lifecycle)
- **src/WeatherService.php** - Weather API client using Open-Meteo's geocoding and forecast endpoints

### Testing

Tests are in the `tests/` directory using PHPUnit:

- **McpServerTest** - Tests MCP protocol handling (tool registration, invocation, JSON-RPC responses, error handling)
- **WeatherServiceTest** - Tests weather data processing (compass direction conversion, weather code mapping, API response handling with mocks)

### Adding New Tools

Register tools in `server.php` using `McpServer::addTool()`:
```php
$server->addTool(
    'tool_name',
    'Tool description',
    ['type' => 'object', 'properties' => [...], 'required' => [...]],
    fn($args) => $result
);
```

### MCP Protocol Flow

1. Client sends `initialize` request
2. Server responds with capabilities (currently only `tools`)
3. Client calls `tools/list` to discover available tools
4. Client calls `tools/call` with tool name and arguments

### Client Configuration

The server uses STDIO transport and can be used with any MCP-compatible client.

**DevoxxGenie (JetBrains IDEs):**
- Transport Type: `STDIO`
- Command: `php`
- Arguments: `/path/to/server.php`

**Claude Desktop:**
Add to `claude_desktop_config.json`:
```json
{
  "mcpServers": {
    "weather": {
      "command": "php",
      "args": ["/path/to/server.php"]
    }
  }
}
```

**Configuration file:** `mcp-servers-config.json` contains a ready-to-use MCP server configuration.
