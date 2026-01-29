# Weather MCP Server (PHP)

A PHP implementation of a Model Context Protocol (MCP) server that provides weather information using the Open-Meteo API (no API key required).

## Requirements

- PHP 8.1 or higher
- `allow_url_fopen` enabled in php.ini

## Installation

```bash
php composer.phar install
```

## Testing

Run the test suite with PHPUnit:

```bash
php phpunit.phar
```

The project includes tests for both core components:

- **McpServerTest** - Tests MCP protocol handling including tool registration, invocation, JSON-RPC responses, and error handling
- **WeatherServiceTest** - Tests weather data processing including compass direction conversion, weather code mapping, and API response handling (uses mocks to avoid HTTP requests)

## Usage

### Running the Server

```bash
php server.php
```

The server communicates via stdio using JSON-RPC 2.0.

### Claude Desktop Configuration

Add to your `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "weather": {
      "command": "php",
      "args": ["/full/path/to/server.php"]
    }
  }
}
```

### DevoxxGenie (IntelliJ IDEA) Configuration

To use this MCP server with [DevoxxGenie](https://github.com/devoxx/DevoxxGenieIDEAPlugin) in IntelliJ IDEA or other JetBrains IDEs:

1. Open DevoxxGenie settings (Settings > Tools > DevoxxGenie)
2. Navigate to the MCP Servers configuration
3. Add a new server with these settings:

| Setting | Value |
|---------|-------|
| **Name** | `php-weather` |
| **Transport Type** | `STDIO` |
| **Command** | `php` |
| **Arguments** | `/full/path/to/server.php` |

Or use the provided `mcp-servers-config.json` file:

```json
{
  "mcpServers": {
    "php-weather": {
      "command": "php",
      "args": [
        "/full/path/to/server.php"
      ],
      "env": {}
    }
  }
}
```

**Note:** Replace `/full/path/to/server.php` with the actual absolute path to `server.php` on your system.

Once configured, DevoxxGenie will discover the `get_current_weather` tool and the AI assistant can use it to fetch weather information.

## Available Tools

### `get_current_weather`

Get current weather conditions for a location.

**Parameters:**
- `location` (required): City name or location (e.g., "London", "New York", "Tokyo")

**Example response:**
```
Current Weather for London, United Kingdom
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Conditions: Overcast
Temperature: 9.3°C (feels like 7.8°C)
Humidity: 88%
Wind: 6.0 km/h from SSE
Coordinates: 51.5085, -0.1257
Updated: 2026-01-19T10:00
```

### `get_forecast`

Get a weather forecast for a location.

**Parameters:**
- `location` (required): City name or location
- `days` (optional): Number of days to forecast (1-16, default: 5)

**Example response:**
```
3-Day Forecast for Tokyo, Japan
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

2026-01-19
  Overcast
  High: 11.1°C | Low: 2.2°C
  Precipitation: 0% | Wind: 4.5 km/h

2026-01-20
  Partly cloudy
  High: 7.1°C | Low: 0.5°C
  Precipitation: 0% | Wind: 13.3 km/h
```

## Project Structure

```
├── composer.json            # Project dependencies
├── server.php               # Main entry point
├── mcp-servers-config.json  # MCP server configuration for clients
├── phpunit.xml              # PHPUnit configuration
├── phpunit.phar             # PHPUnit executable
├── src/
│   ├── McpServer.php        # MCP protocol implementation
│   └── WeatherService.php   # Weather API client
├── tests/
│   ├── McpServerTest.php    # MCP server tests
│   └── WeatherServiceTest.php # Weather service tests
└── README.md
```

## API Source

Weather data is provided by [Open-Meteo](https://open-meteo.com/), a free weather API that requires no API key.
