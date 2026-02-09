<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strip injected HTML (e.g. spam comments/links from server/hosting) that appears
 * before Livewire's JSON response. Fixes "Unexpected token '<'" when the response
 * is polluted with content like "<!-- This Commentary... --><div>...</div>{...".
 */
class StripHtmlFromLivewireResponse
{
    /** Max chars to log for raw/stripped content (avoid huge logs). */
    private const LOG_PREVIEW_LENGTH = 500;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->hasHeader('X-Livewire')) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            Log::warning('Livewire response: empty content', [
                'url' => $request->fullUrl(),
                'status' => $response->getStatusCode(),
            ]);
            return $response;
        }

        $trimmed = ltrim($content);
        if ($trimmed === '') {
            Log::warning('Livewire response: whitespace only after trim', [
                'url' => $request->fullUrl(),
                'content_length' => strlen($content),
            ]);
            return $response;
        }

        if ($trimmed[0] === '{') {
            return $response;
        }

        $jsonStart = strpos($trimmed, '{');
        if ($jsonStart === false) {
            Log::error('Livewire response: no JSON object found (invalid response)', [
                'url' => $request->fullUrl(),
                'status' => $response->getStatusCode(),
                'content_length' => strlen($content),
                'content_preview' => mb_substr($content, 0, self::LOG_PREVIEW_LENGTH),
                'content_type' => $response->headers->get('Content-Type'),
            ]);
            return $response;
        }

        $stripped = substr($trimmed, $jsonStart);
        $strippedLength = $jsonStart;

        Log::warning('Livewire response: stripped leading HTML before JSON', [
            'url' => $request->fullUrl(),
            'stripped_bytes' => $strippedLength,
            'raw_preview' => mb_substr($content, 0, self::LOG_PREVIEW_LENGTH),
            'json_preview' => mb_substr($stripped, 0, 200),
        ]);

        $response->setContent($stripped);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->remove('Content-Length');

        return $response;
    }
}
