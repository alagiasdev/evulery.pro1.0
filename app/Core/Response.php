<?php

namespace App\Core;

class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $message = '', int $status = 200): void
    {
        $response = ['success' => true];
        if ($data !== null) {
            $response['data'] = $data;
        }
        if ($message) {
            $response['message'] = $message;
        }
        self::json($response, $status);
    }

    public static function error(string $message, string $code = 'ERROR', int $status = 400, array $extra = []): void
    {
        $response = [
            'success' => false,
            'error' => [
                'code'    => $code,
                'message' => $message,
            ],
        ];
        self::json(array_merge($response, $extra), $status);
    }

    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: $url");
        exit;
    }

    public static function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        self::redirect($referer);
    }

    public static function notFound(): void
    {
        http_response_code(404);
        if (file_exists(BASE_PATH . '/views/errors/404.php')) {
            require BASE_PATH . '/views/errors/404.php';
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
        exit;
    }

    public static function serverError(string $message = ''): void
    {
        http_response_code(500);
        if (file_exists(BASE_PATH . '/views/errors/500.php')) {
            require BASE_PATH . '/views/errors/500.php';
        } else {
            echo '<h1>500 - Internal Server Error</h1>';
            if (env('APP_DEBUG', false) && $message) {
                echo '<pre>' . htmlspecialchars($message) . '</pre>';
            }
        }
        exit;
    }
}
