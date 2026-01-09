<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Centralized logging service for structured, consistent logging across the application.
 * 
 * Provides:
 * - Trace ID generation for request correlation
 * - Structured action/event logging
 * - Error logging with context
 * - Business event logging
 */
class LoggingService
{
    protected static ?string $traceId = null;

    /**
     * Get or generate a unique trace ID for the current request.
     * This ID correlates all log entries for a single request.
     */
    public static function getTraceId(): string
    {
        if (!static::$traceId) {
            static::$traceId = (string) Str::uuid();
        }

        return static::$traceId;
    }

    /**
     * Reset the trace ID (useful for testing or queue workers).
     */
    public static function resetTraceId(): void
    {
        static::$traceId = null;
    }

    /**
     * Log a generic action with context.
     *
     * @param string $action The action being performed (e.g., 'user.login', 'reservation.created')
     * @param array $context Additional context data
     * @param string $level Log level (info, warning, error, debug)
     * @param string $channel Log channel to use
     */
    public static function logAction(
        string $action,
        array $context = [],
        string $level = 'info',
        string $channel = 'api'
    ): void {
        $enrichedContext = static::enrichContext($context, [
            'action' => $action,
        ]);

        Log::channel($channel)->{$level}($action, $enrichedContext);
    }

    /**
     * Log a business event related to a model.
     *
     * @param string $event The event name (e.g., 'created', 'updated', 'status_changed')
     * @param Model $subject The model the event is about
     * @param array $data Additional event data
     * @param string|null $causedBy User/admin ID who caused the event
     */
    public static function logBusinessEvent(
        string $event,
        Model $subject,
        array $data = [],
        ?string $causedBy = null
    ): void {
        $context = [
            'event' => $event,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'changes' => $data,
        ];

        if ($causedBy) {
            $context['caused_by'] = $causedBy;
        }

        $enrichedContext = static::enrichContext($context);

        $message = sprintf('%s.%s', class_basename($subject), $event);
        Log::channel('activity')->info($message, $enrichedContext);
    }

    /**
     * Log an error with full context.
     *
     * @param Throwable $exception The exception that occurred
     * @param array $context Additional context
     * @param string $channel Log channel to use
     */
    public static function logError(
        Throwable $exception,
        array $context = [],
        string $channel = 'api'
    ): void {
        $errorContext = [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
        ];

        $enrichedContext = static::enrichContext(array_merge($context, $errorContext));

        Log::channel($channel)->error($exception->getMessage(), $enrichedContext);
    }

    /**
     * Log a warning.
     *
     * @param string $message Warning message
     * @param array $context Additional context
     * @param string $channel Log channel to use
     */
    public static function logWarning(
        string $message,
        array $context = [],
        string $channel = 'api'
    ): void {
        $enrichedContext = static::enrichContext($context);
        Log::channel($channel)->warning($message, $enrichedContext);
    }

    /**
     * Log debug information (only in non-production).
     *
     * @param string $message Debug message
     * @param array $context Additional context
     * @param string $channel Log channel to use
     */
    public static function logDebug(
        string $message,
        array $context = [],
        string $channel = 'api'
    ): void {
        if (app()->environment('production')) {
            return;
        }

        $enrichedContext = static::enrichContext($context);
        Log::channel($channel)->debug($message, $enrichedContext);
    }

    /**
     * Log a slow operation (performance monitoring).
     *
     * @param string $operation The operation name
     * @param float $durationMs Duration in milliseconds
     * @param array $context Additional context
     */
    public static function logSlowOperation(
        string $operation,
        float $durationMs,
        array $context = []
    ): void {
        $enrichedContext = static::enrichContext(array_merge($context, [
            'operation' => $operation,
            'duration_ms' => $durationMs,
        ]));

        Log::channel('api')->warning('Slow Operation', $enrichedContext);
    }

    /**
     * Log API request for auditing.
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param int $statusCode Response status code
     * @param float $durationMs Request duration
     * @param array $context Additional context
     */
    public static function logApiRequest(
        string $method,
        string $url,
        int $statusCode,
        float $durationMs,
        array $context = []
    ): void {
        $requestContext = [
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
        ];

        $enrichedContext = static::enrichContext(array_merge($context, $requestContext));

        Log::channel('api')->info('API Request', $enrichedContext);
    }

    /**
     * Enrich context with common fields.
     *
     * @param array $context The original context
     * @param array $additional Additional fields to add
     * @return array Enriched context
     */
    protected static function enrichContext(array $context, array $additional = []): array
    {
        $baseContext = [
            'trace_id' => static::getTraceId(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Add user context if available
        if (auth()->check()) {
            $user = auth()->user();
            $baseContext['user_id'] = $user->id;
            $baseContext['user_type'] = get_class($user);
        }

        // Add request context if in HTTP context
        if (app()->runningInConsole() === false && request()) {
            $baseContext['ip'] = request()->ip();
            $baseContext['user_agent'] = request()->userAgent();
        }

        return array_merge($baseContext, $context, $additional);
    }
}
