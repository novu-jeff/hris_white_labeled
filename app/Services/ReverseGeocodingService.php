<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ReverseGeocodingService
{
    /**
     * Reverse geocode coordinates to a human-readable place string.
     *
     * Provider selection:
     * - GEOCODING_PROVIDER=auto (default): Mapbox if MAPBOX_API is set, else nominatim
     * - GEOCODING_PROVIDER=mapbox
     * - GEOCODING_PROVIDER=nominatim
     * - GEOCODING_PROVIDER=none
     */
    public function reverse(float $lat, float $lng): array
    {
        $provider = strtolower((string) env('GEOCODING_PROVIDER', 'auto'));

        $mapboxToken = (string) env('MAPBOX_API', '');
        $hasMapbox = trim($mapboxToken) !== '';

        if ($provider === 'auto') {
            $provider = $hasMapbox ? 'mapbox' : 'nominatim';
        }

        return match ($provider) {
            'mapbox' => $this->reverseMapbox($lat, $lng, $mapboxToken),
            'nominatim' => $this->reverseNominatim($lat, $lng),
            default => [
                'provider' => 'none',
                'place' => null,
            ],
        };
    }

    private function reverseMapbox(float $lat, float $lng, string $token): array
    {
        if (trim($token) === '') {
            return [
                'provider' => 'mapbox',
                'place' => null,
                'error' => 'Missing MAPBOX_API token.',
            ];
        }

        try {
            $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$lng},{$lat}.json";
            $response = Http::timeout(6)->get($url, [
                'access_token' => $token,
                // Prefer most precise result.
                'limit' => 1,
            ]);

            if (!$response->successful()) {
                return [
                    'provider' => 'mapbox',
                    'place' => null,
                    'error' => "Mapbox request failed ({$response->status()}).",
                ];
            }

            $decoded = $response->json();
            $feature = $decoded['features'][0] ?? null;

            return [
                'provider' => 'mapbox',
                'place' => $feature['place_name'] ?? null,
                'raw' => $feature,
            ];
        } catch (\Throwable $e) {
            return [
                'provider' => 'mapbox',
                'place' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function reverseNominatim(float $lat, float $lng): array
    {
        try {
            $userAgent = (string) env('NOMINATIM_USER_AGENT', env('APP_NAME', 'HRIS') . ' (reverse-geocoding)');

            $response = Http::timeout(6)
                ->withHeaders([
                    // Nominatim requires a valid UA identifying the application.
                    'User-Agent' => $userAgent,
                    'Accept' => 'application/json',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if (!$response->successful()) {
                return [
                    'provider' => 'nominatim',
                    'place' => null,
                    'error' => "Nominatim request failed ({$response->status()}).",
                ];
            }

            $decoded = $response->json();

            return [
                'provider' => 'nominatim',
                'place' => $decoded['display_name'] ?? null,
                'raw' => $decoded,
            ];
        } catch (\Throwable $e) {
            return [
                'provider' => 'nominatim',
                'place' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}

