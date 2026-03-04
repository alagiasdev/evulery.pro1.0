<?php

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $fullUri;
    private array $query;
    private array $body;
    private array $params;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->fullUri = $_SERVER['REQUEST_URI'];
        $this->query = $_GET;
        $this->body = $_POST;
        $this->params = [];

        // Parse URI: remove base path and query string
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = parse_url(env('APP_URL', ''), PHP_URL_PATH) ?: '';

        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        // Remove /public prefix if present
        if (str_starts_with($uri, '/public')) {
            $uri = substr($uri, 7);
        }

        $this->uri = '/' . trim($uri, '/');
        if ($this->uri !== '/') {
            $this->uri = rtrim($this->uri, '/');
        }
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function fullUri(): string
    {
        return $this->fullUri;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($contentType, 'application/json');
    }

    public function json(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: [];
    }

    public function host(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    public function isEmbed(): bool
    {
        return $this->query('embed') === '1';
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
