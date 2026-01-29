<?php

declare(strict_types=1);

namespace Tests;

use App\WeatherService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class WeatherServiceTest extends TestCase
{
    private WeatherService $service;

    protected function setUp(): void
    {
        $this->service = new WeatherService();
    }

    /**
     * Test that degreesToCompass accurately converts numerical degrees to cardinal and intercardinal directions.
     *
     * @dataProvider degreesToCompassProvider
     */
    public function testDegreesToCompassConvertsCorrectly(float $degrees, string $expected): void
    {
        $method = new ReflectionMethod(WeatherService::class, 'degreesToCompass');

        $result = $method->invoke($this->service, $degrees);

        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for degreesToCompass tests.
     *
     * @return array<string, array{float, string}>
     */
    public static function degreesToCompassProvider(): array
    {
        return [
            // Cardinal directions
            'North at 0°' => [0.0, 'N'],
            'North at 360°' => [360.0, 'N'],
            'East at 90°' => [90.0, 'E'],
            'South at 180°' => [180.0, 'S'],
            'West at 270°' => [270.0, 'W'],

            // Intercardinal directions
            'NE at 45°' => [45.0, 'NE'],
            'SE at 135°' => [135.0, 'SE'],
            'SW at 225°' => [225.0, 'SW'],
            'NW at 315°' => [315.0, 'NW'],

            // Secondary intercardinal directions
            'NNE at 22.5°' => [22.5, 'NNE'],
            'ENE at 67.5°' => [67.5, 'ENE'],
            'ESE at 112.5°' => [112.5, 'ESE'],
            'SSE at 157.5°' => [157.5, 'SSE'],
            'SSW at 202.5°' => [202.5, 'SSW'],
            'WSW at 247.5°' => [247.5, 'WSW'],
            'WNW at 292.5°' => [292.5, 'WNW'],
            'NNW at 337.5°' => [337.5, 'NNW'],

            // Boundary cases - values just before transitions
            'N boundary low' => [11.24, 'N'],
            'NNE boundary low' => [11.26, 'NNE'],
            'NNE boundary high' => [33.74, 'NNE'],
            'NE boundary low' => [33.76, 'NE'],

            // Edge cases
            'Small positive' => [5.0, 'N'],
            'Near 360°' => [355.0, 'N'],
        ];
    }

    /**
     * Test that weatherCodeToDescription accurately maps WMO weather codes to human-readable descriptions.
     *
     * @dataProvider weatherCodeProvider
     */
    public function testWeatherCodeToDescriptionMapsCorrectly(int $code, string $expected): void
    {
        $method = new ReflectionMethod(WeatherService::class, 'weatherCodeToDescription');

        $result = $method->invoke($this->service, $code);

        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for weatherCodeToDescription tests.
     *
     * @return array<string, array{int, string}>
     */
    public static function weatherCodeProvider(): array
    {
        return [
            // Clear/cloudy conditions
            'Clear sky (0)' => [0, 'Clear sky'],
            'Mainly clear (1)' => [1, 'Mainly clear'],
            'Partly cloudy (2)' => [2, 'Partly cloudy'],
            'Overcast (3)' => [3, 'Overcast'],

            // Fog
            'Fog (45)' => [45, 'Foggy'],
            'Depositing rime fog (48)' => [48, 'Foggy'],

            // Drizzle
            'Light drizzle (51)' => [51, 'Light drizzle'],
            'Moderate drizzle (53)' => [53, 'Moderate drizzle'],
            'Dense drizzle (55)' => [55, 'Dense drizzle'],
            'Light freezing drizzle (56)' => [56, 'Freezing drizzle'],
            'Dense freezing drizzle (57)' => [57, 'Freezing drizzle'],

            // Rain
            'Slight rain (61)' => [61, 'Slight rain'],
            'Moderate rain (63)' => [63, 'Moderate rain'],
            'Heavy rain (65)' => [65, 'Heavy rain'],
            'Light freezing rain (66)' => [66, 'Freezing rain'],
            'Heavy freezing rain (67)' => [67, 'Freezing rain'],

            // Snow
            'Slight snow (71)' => [71, 'Slight snow'],
            'Moderate snow (73)' => [73, 'Moderate snow'],
            'Heavy snow (75)' => [75, 'Heavy snow'],
            'Snow grains (77)' => [77, 'Snow grains'],

            // Showers
            'Slight rain showers (80)' => [80, 'Slight rain showers'],
            'Moderate rain showers (81)' => [81, 'Moderate rain showers'],
            'Violent rain showers (82)' => [82, 'Violent rain showers'],
            'Slight snow showers (85)' => [85, 'Slight snow showers'],
            'Heavy snow showers (86)' => [86, 'Heavy snow showers'],

            // Thunderstorm
            'Thunderstorm (95)' => [95, 'Thunderstorm'],
            'Thunderstorm with slight hail (96)' => [96, 'Thunderstorm with hail'],
            'Thunderstorm with heavy hail (99)' => [99, 'Thunderstorm with hail'],

            // Unknown codes
            'Unknown code (100)' => [100, 'Unknown'],
            'Unknown code (-1)' => [-1, 'Unknown'],
            'Unknown code (50)' => [50, 'Unknown'],
        ];
    }

    /**
     * Test that getCurrentWeather returns correctly structured weather data.
     * This test uses a mock to avoid making actual HTTP requests.
     */
    public function testGetCurrentWeatherReturnsCorrectStructure(): void
    {
        // Create a partial mock that overrides the httpGet method
        $service = $this->getMockBuilder(WeatherService::class)
            ->onlyMethods([])
            ->getMock();

        // Use reflection to override httpGet with mocked responses
        $httpGetMethod = new ReflectionMethod(WeatherService::class, 'httpGet');

        // Create a testable service by extending and overriding httpGet
        $mockService = new class extends WeatherService {
            private int $callCount = 0;

            protected function httpGet(string $url): string
            {
                $this->callCount++;

                // First call is geocoding
                if ($this->callCount === 1) {
                    return json_encode([
                        'results' => [
                            [
                                'name' => 'London',
                                'latitude' => 51.5074,
                                'longitude' => -0.1278,
                                'country' => 'United Kingdom',
                            ],
                        ],
                    ]);
                }

                // Second call is weather data
                return json_encode([
                    'current' => [
                        'time' => '2024-01-15T12:00',
                        'temperature_2m' => 8.5,
                        'relative_humidity_2m' => 75,
                        'apparent_temperature' => 6.2,
                        'weather_code' => 3,
                        'wind_speed_10m' => 15.5,
                        'wind_direction_10m' => 225.0,
                    ],
                ]);
            }
        };

        $result = $mockService->getCurrentWeather('London');

        // Verify structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('location', $result);
        $this->assertArrayHasKey('country', $result);
        $this->assertArrayHasKey('coordinates', $result);
        $this->assertArrayHasKey('temperature', $result);
        $this->assertArrayHasKey('humidity', $result);
        $this->assertArrayHasKey('wind', $result);
        $this->assertArrayHasKey('conditions', $result);
        $this->assertArrayHasKey('timestamp', $result);

        // Verify nested structures
        $this->assertArrayHasKey('latitude', $result['coordinates']);
        $this->assertArrayHasKey('longitude', $result['coordinates']);
        $this->assertArrayHasKey('current', $result['temperature']);
        $this->assertArrayHasKey('feels_like', $result['temperature']);
        $this->assertArrayHasKey('unit', $result['temperature']);
        $this->assertArrayHasKey('speed', $result['wind']);
        $this->assertArrayHasKey('direction', $result['wind']);
        $this->assertArrayHasKey('unit', $result['wind']);

        // Verify values
        $this->assertSame('London', $result['location']);
        $this->assertSame('United Kingdom', $result['country']);
        $this->assertSame(51.5074, $result['coordinates']['latitude']);
        $this->assertSame(-0.1278, $result['coordinates']['longitude']);
        $this->assertSame(8.5, $result['temperature']['current']);
        $this->assertSame(6.2, $result['temperature']['feels_like']);
        $this->assertSame('°C', $result['temperature']['unit']);
        $this->assertSame('75%', $result['humidity']);
        $this->assertSame(15.5, $result['wind']['speed']);
        $this->assertSame('SW', $result['wind']['direction']);
        $this->assertSame('km/h', $result['wind']['unit']);
        $this->assertSame('Overcast', $result['conditions']);
        $this->assertSame('2024-01-15T12:00', $result['timestamp']);
    }

    /**
     * Test that getCurrentWeather throws exception for unknown location.
     */
    public function testGetCurrentWeatherThrowsForUnknownLocation(): void
    {
        $mockService = new class extends WeatherService {
            protected function httpGet(string $url): string
            {
                // Return empty results for geocoding
                return json_encode(['results' => []]);
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Location not found');

        $mockService->getCurrentWeather('NonexistentPlace12345');
    }

    /**
     * Test that getCurrentWeather throws exception when weather API fails.
     */
    public function testGetCurrentWeatherThrowsWhenWeatherApiFails(): void
    {
        $mockService = new class extends WeatherService {
            private int $callCount = 0;

            protected function httpGet(string $url): string
            {
                $this->callCount++;

                if ($this->callCount === 1) {
                    return json_encode([
                        'results' => [
                            [
                                'name' => 'London',
                                'latitude' => 51.5074,
                                'longitude' => -0.1278,
                            ],
                        ],
                    ]);
                }

                // Return response without 'current' key
                return json_encode(['error' => 'API error']);
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch weather data');

        $mockService->getCurrentWeather('London');
    }
}
