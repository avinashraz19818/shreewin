<?php

/**
 * Central PHP error logger.
 *
 * All entries are written to the project-root error.log as JSON lines. Request
 * bodies, authorization headers, cookies, tokens and payment identifiers are
 * deliberately not recorded.
 */

if (!defined('APP_ERROR_LOGGER_BOOTED')) {
    define('APP_ERROR_LOGGER_BOOTED', true);
    define('APP_ERROR_LOG_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'error.log');

    date_default_timezone_set('Asia/Kolkata');
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ERROR_LOG_PATH);

    function app_request_id(): string
    {
        static $requestId = null;
        if ($requestId !== null) {
            return $requestId;
        }

        $incoming = trim((string)($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
        if ($incoming !== '' && preg_match('/^[A-Za-z0-9._-]{8,80}$/', $incoming)) {
            $requestId = $incoming;
            return $requestId;
        }

        try {
            $requestId = bin2hex(random_bytes(8));
        } catch (Throwable $e) {
            $requestId = str_replace('.', '', uniqid('req', true));
        }
        return $requestId;
    }

    function app_error_type(int $severity): string
    {
        $types = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];
        return $types[$severity] ?? ('E_' . $severity);
    }

    function app_sanitize_log_context($value, string $key = '', int $depth = 0)
    {
        if ($depth > 5) {
            return '[MAX_DEPTH]';
        }
        if ($key !== '' && preg_match('/pass(word)?|token|secret|signature|authorization|cookie|session|otp|pin|cvv|card|account|upi|utr/i', $key)) {
            return '[REDACTED]';
        }
        if (is_array($value)) {
            $clean = [];
            foreach (array_slice($value, 0, 50, true) as $itemKey => $itemValue) {
                $clean[$itemKey] = app_sanitize_log_context($itemValue, (string)$itemKey, $depth + 1);
            }
            return $clean;
        }
        if (is_object($value)) {
            return '[OBJECT ' . get_class($value) . ']';
        }
        if (is_resource($value)) {
            return '[RESOURCE]';
        }
        if (is_string($value) && strlen($value) > 1000) {
            return substr($value, 0, 1000) . '[TRUNCATED]';
        }
        return $value;
    }

    function app_safe_request_uri(string $uri): string
    {
        if ($uri === '') {
            return '';
        }

        $question = strpos($uri, '?');
        if ($question === false) {
            return substr($uri, 0, 2000);
        }

        $path = substr($uri, 0, $question);
        $query = substr($uri, $question + 1);
        $values = [];
        parse_str($query, $values);
        if (!is_array($values)) {
            return substr($path, 0, 1900) . '?[REDACTED_QUERY]';
        }

        foreach ($values as $key => $value) {
            if (preg_match('/pass(word)?|token|secret|signature|authorization|cookie|session|otp|pin|cvv|card|account|upi|utr/i', (string)$key)) {
                $values[$key] = '[REDACTED]';
            }
        }

        $safeQuery = http_build_query($values, '', '&', PHP_QUERY_RFC3986);
        return substr($path . ($safeQuery !== '' ? '?' . $safeQuery : ''), 0, 2000);
    }

    function app_log_event(string $level, string $message, array $context = []): void
    {
        $entry = [
            'time' => date(DATE_ATOM),
            'level' => strtoupper($level),
            'request_id' => app_request_id(),
            'message' => $message,
            'request' => [
                'method' => (string)($_SERVER['REQUEST_METHOD'] ?? 'CLI'),
                'uri' => app_safe_request_uri((string)($_SERVER['REQUEST_URI'] ?? '')),
                'script' => (string)($_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '')),
            ],
            'context' => app_sanitize_log_context($context),
        ];

        $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if (!is_string($line)) {
            $line = '{"level":"ERROR","message":"Unable to encode log entry"}';
        }
        @error_log($line . PHP_EOL, 3, APP_ERROR_LOG_PATH);
    }

    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('X-Request-Id: ' . app_request_id());
        header('Access-Control-Expose-Headers: X-Request-Id');
    }

    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        app_log_event('error', 'PHP runtime error', [
            'type' => app_error_type($severity),
            'error' => $message,
            'file' => $file,
            'line' => $line,
        ]);
        return true;
    });

    set_exception_handler(static function (Throwable $exception): void {
        $trace = [];
        foreach (array_slice($exception->getTrace(), 0, 20) as $frame) {
            $trace[] = [
                'file' => $frame['file'] ?? '',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? '',
                'class' => $frame['class'] ?? '',
            ];
        }
        app_log_event('critical', 'Uncaught exception', [
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $trace,
        ]);

        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'data' => null,
                'code' => 8,
                'msg' => 'Internal server error',
                'msgCode' => 8,
                'requestId' => app_request_id(),
                'serviceNowTime' => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    });

    register_shutdown_function(static function (): void {
        $lastError = error_get_last();
        if (!is_array($lastError)) {
            return;
        }
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if (!in_array((int)$lastError['type'], $fatalTypes, true)) {
            return;
        }
        app_log_event('critical', 'PHP fatal shutdown error', [
            'type' => app_error_type((int)$lastError['type']),
            'error' => (string)$lastError['message'],
            'file' => (string)$lastError['file'],
            'line' => (int)$lastError['line'],
        ]);
    });
}
