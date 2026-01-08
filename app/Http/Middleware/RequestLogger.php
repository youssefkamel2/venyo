<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = \App\Services\LoggingService::getTraceId();
        $startTime = microtime(true);

        $response = $next($request);

        $duration = number_format((microtime(true) - $startTime) * 1000, 2);

        $logData = [
            'trace_id' => $traceId,
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'duration_ms' => $duration,
            'status' => $response->getStatusCode(),
            'payload' => $this->sanitizePayload($request->all()),
            'user_id' => $request->user()?->id,
        ];

        Log::channel('api')->info('API Request', $logData);

        // Add Trace-ID to response headers
        $response->headers->set('X-Trace-ID', $traceId);

        return $response;
    }

    /**
     * Sanitize the request payload to obfuscate sensitive fields.
     */
    protected function sanitizePayload(array $payload): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'temp_password', 'token'];

        foreach ($sensitiveFields as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = '********';
            }
        }

        return $payload;
    }
}
