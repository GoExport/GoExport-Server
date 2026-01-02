<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->getApiKeyFromRequest($request);

        if (!$apiKey) {
            return response()->json([
                'error' => 'API key is required',
                'message' => 'Please provide an API key via the X-API-Key header or api_key query parameter.',
            ], 401);
        }

        $key = ApiKey::findByKey($apiKey);

        if (!$key) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid, inactive, or has expired.',
            ], 401);
        }

        // Mark the key as used
        $key->markAsUsed();

        // Store the API key and user on the request for later use
        $request->attributes->set('api_key', $key);
        $request->attributes->set('api_user', $key->user);

        return $next($request);
    }

    /**
     * Get the API key from the request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getApiKeyFromRequest(Request $request): ?string
    {
        // Check the X-API-Key header first
        $apiKey = $request->header('X-API-Key');

        if ($apiKey) {
            return $apiKey;
        }

        // Fall back to query parameter
        return $request->query('api_key');
    }
}
