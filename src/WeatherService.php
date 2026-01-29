<?php

declare(strict_types=1);

namespace App;

/**
 * Weather service that fetches data from Open-Meteo API.
 * No API key required.
 */
class WeatherService
{
    private const GEOCODING_URL = 'https://geocoding-api.open-meteo.com/v1/search';
    private const WEATHER_URL = 'https://api.open-meteo.com/v1/forecast';

    /**
     * Get current weather for a location.
     */
    public function getCurrentWeather(string $location): array
    {
        // First, geocode the location
        $coords = $this->geocode($location);

        // Then fetch weather data
        $params = http_build_query([
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,wind_direction_10m',
            'timezone' => 'auto',
        ]);

        $url = self::WEATHER_URL . '?' . $params;
        $response = $this->httpGet($url);
        $data = json_decode($response, true);

        if (!isset($data['current'])) {
            throw new \RuntimeException('Failed to fetch weather data');
        }

        $current = $data['current'];

        return [
            'location' => $coords['name'],
            'country' => $coords['country'] ?? '',
            'coordinates' => [
                'latitude' => $coords['latitude'],
                'longitude' => $coords['longitude'],
            ],
            'temperature' => [
                'current' => $current['temperature_2m'],
                'feels_like' => $current['apparent_temperature'],
                'unit' => '°C',
            ],
            'humidity' => $current['relative_humidity_2m'] . '%',
            'wind' => [
                'speed' => $current['wind_speed_10m'],
                'direction' => $this->degreesToCompass($current['wind_direction_10m']),
                'unit' => 'km/h',
            ],
            'conditions' => $this->weatherCodeToDescription($current['weather_code']),
            'timestamp' => $current['time'],
        ];
    }

    /**
     * Geocode a location name to coordinates.
     */
    protected function geocode(string $location): array
    {
        $params = http_build_query([
            'name' => $location,
            'count' => 1,
            'language' => 'en',
            'format' => 'json',
        ]);

        $url = self::GEOCODING_URL . '?' . $params;
        $response = $this->httpGet($url);
        $data = json_decode($response, true);

        if (empty($data['results'])) {
            throw new \RuntimeException("Location not found: {$location}");
        }

        return $data['results'][0];
    }

    /**
     * Perform an HTTP GET request.
     */
    protected function httpGet(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: PHP-MCP-Weather-Server/1.0',
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException('HTTP request failed');
        }

        return $response;
    }

    /**
     * Convert wind direction degrees to compass direction.
     */
    private function degreesToCompass(float $degrees): string
    {
        $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
        $index = (int) round($degrees / 22.5) % 16;
        return $directions[$index];
    }

    /**
     * Convert WMO weather code to human-readable description.
     */
    private function weatherCodeToDescription(int $code): string
    {
        return match ($code) {
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45, 48 => 'Foggy',
            51 => 'Light drizzle',
            53 => 'Moderate drizzle',
            55 => 'Dense drizzle',
            56, 57 => 'Freezing drizzle',
            61 => 'Slight rain',
            63 => 'Moderate rain',
            65 => 'Heavy rain',
            66, 67 => 'Freezing rain',
            71 => 'Slight snow',
            73 => 'Moderate snow',
            75 => 'Heavy snow',
            77 => 'Snow grains',
            80 => 'Slight rain showers',
            81 => 'Moderate rain showers',
            82 => 'Violent rain showers',
            85 => 'Slight snow showers',
            86 => 'Heavy snow showers',
            95 => 'Thunderstorm',
            96, 99 => 'Thunderstorm with hail',
            default => 'Unknown',
        };
    }
}
