#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$server = new App\McpServer('weather-mcp', '1.0.0');
$weather = new App\WeatherService();

$server->addTool('get_current_weather', 'Get current weather for a location', [
    'type' => 'object',
    'properties' => ['location' => ['type' => 'string', 'description' => 'City name']],
    'required' => ['location'],
], fn($args) => json_encode($weather->getCurrentWeather($args['location']), JSON_PRETTY_PRINT));

$server->run();
