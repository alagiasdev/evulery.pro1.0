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
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Dietro Cloudflare il vero IP del visitatore arriva in CF-Connecting-IP.
        // Lo usiamo SOLO se la connessione proviene davvero da un IP Cloudflare:
        // altrimenti l'header sarebbe falsificabile colpendo l'origin diretto, e
        // bypasserebbe rate limit / login throttle. Gated da TRUST_CLOUDFLARE
        // (default off) per rollout sicuro: si attiva quando Cloudflare e' davanti.
        if ((string)env('TRUST_CLOUDFLARE', '0') === '1'
            && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])
            && self::isCloudflareIp($remote)) {
            $cf = trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cf, FILTER_VALIDATE_IP)) {
                return $cf;
            }
        }

        return $remote;
    }

    /** L'IP della connessione appartiene ai range pubblici di Cloudflare? */
    private static function isCloudflareIp(string $ip): bool
    {
        // Fonte: https://www.cloudflare.com/ips/ — aggiornare se Cloudflare cambia i range.
        static $ranges = [
            // IPv4
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
            '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
            '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            // IPv6
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
            '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
        ];
        foreach ($ranges as $cidr) {
            if (self::ipInCidr($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }

    /** Match IP (v4/v6) contro un CIDR, byte-wise su inet_pton. */
    private static function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return false;
        }
        [$subnet, $bitsRaw] = explode('/', $cidr, 2);
        $bits   = (int)$bitsRaw;
        $ipBin  = @inet_pton($ip);
        $subBin = @inet_pton($subnet);
        if ($ipBin === false || $subBin === false || strlen($ipBin) !== strlen($subBin)) {
            return false; // versioni diverse (v4 vs v6) o IP non valido
        }
        $bytes = intdiv($bits, 8);
        $rem   = $bits % 8;
        if ($bytes > 0 && strncmp($ipBin, $subBin, $bytes) !== 0) {
            return false;
        }
        if ($rem === 0) {
            return true;
        }
        $mask = chr((0xff << (8 - $rem)) & 0xff);
        return (($ipBin[$bytes] ^ $subBin[$bytes]) & $mask) === "\0";
    }
}
