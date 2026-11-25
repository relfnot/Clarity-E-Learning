<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private string $method;
    private string $path;
    /** @var array<string, mixed> */
    private array $query;
    /** @var array<string, mixed> */
    private array $body;
    /** @var array<string, string> */
    private array $headers;
    /** @var array<string, string> */
    private array $routeParams = [];
    /** @var array<string, mixed>|null */
    private ?array $user = null;

    private function __construct(
        string $method,
        string $path,
        array $query,
        array $body,
        array $headers
    ) {
        $this->method = $method;
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
    }

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $rawUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $parsedPath = parse_url($rawUri, PHP_URL_PATH);
        $path = self::normalizeApiPath(is_string($parsedPath) ? $parsedPath : '/');

        $headers = self::extractHeaders();
        $body = self::parseBody($method, $headers);

        return new self($method, $path, $_GET, $body, $headers);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        return $this->body;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower($name);
        return $this->headers[$normalized] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $authHeader = $this->header('authorization');
        if ($authHeader === null || $authHeader === '') {
            return null;
        }

        if (preg_match('/^\s*Bearer\s+(.+)$/i', $authHeader, $matches) === 1) {
            $token = trim($matches[1]);
            return $token !== '' ? $token : null;
        }

        return null;
    }

    public function clientIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        return $ip !== '' ? $ip : '127.0.0.1';
    }

    /**
     * @param array<string, string> $params
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function intParam(string $key): int
    {
        $raw = $this->param($key);
        if ($raw === null || preg_match('/^\d+$/', $raw) !== 1 || (int) $raw <= 0) {
            throw new HttpException(
                'VALIDATION_ERROR',
                "Parameter {$key} harus berupa bilangan bulat positif.",
                400,
                [$key => ["Parameter {$key} tidak valid."]]
            );
        }

        return (int) $raw;
    }

    public function queryInt(string $key, int $default, int $min = 1, int $max = 100): int
    {
        $raw = $this->query($key);
        if ($raw === null || $raw === '') {
            return $default;
        }
        $val = (int) $raw;
        if ($val < $min) {
            return $min;
        }
        if ($val > $max) {
            return $max;
        }
        return $val;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        return is_array($file) ? $file : null;
    }

    /**
     * @param array<string, mixed>|null $user
     */
    public function setUser(?array $user): void
    {
        $this->user = $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        return $this->user;
    }

    private static function normalizeApiPath(string $rawPath): string
    {
        $decoded = rawurldecode($rawPath);
        $apiPrefixPos = stripos($decoded, '/api/v1');

        if ($apiPrefixPos !== false) {
            $path = substr($decoded, $apiPrefixPos + strlen('/api/v1'));
        } else {
            $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
            $path = $decoded;
            if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
                $path = substr($path, strlen($scriptDir));
            }
            if (str_starts_with($path, '/index.php')) {
                $path = substr($path, strlen('/index.php'));
            }
        }

        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * @return array<string, string>
     */
    private static function extractHeaders(): array
    {
        $headers = [];

        if (function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            if (is_array($apacheHeaders)) {
                foreach ($apacheHeaders as $name => $value) {
                    $headers[strtolower((string) $name)] = trim((string) $value);
                }
            }
        }

        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$headerName] = trim($value);
            } elseif ($key === 'CONTENT_TYPE') {
                $headers['content-type'] = trim($value);
            } elseif ($key === 'REDIRECT_HTTP_AUTHORIZATION' && !isset($headers['authorization'])) {
                $headers['authorization'] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private static function parseBody(string $method, array $headers): array
    {
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return [];
        }

        $contentType = strtolower($headers['content-type'] ?? '');
        $raw = file_get_contents('php://input');

        if (is_string($raw) && trim($raw) !== '') {
            if (str_contains($contentType, 'application/json') || str_starts_with(ltrim($raw), '{') || str_starts_with(ltrim($raw), '[')) {
                $decoded = json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new HttpException(
                        'VALIDATION_ERROR',
                        'Format JSON pada request body tidak valid.',
                        400
                    );
                }

                return is_array($decoded) ? $decoded : [];
            }
        }

        return $_POST;
    }
}
