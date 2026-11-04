<?php
declare(strict_types=1);

namespace TinyPHP\Http;

final class Request
{
    private function __construct(
        private readonly array $get,
        private readonly array $post,
        private readonly array $files,
        private readonly array $server
    ) {}

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_FILES, $_SERVER);
    }

    /** Read a key from the query string, e.g. $request->query('page')->int(1). */
    public function query(string $key): Input
    {
        return new Input($this->get[$key] ?? null, array_key_exists($key, $this->get));
    }

    /** Read a key from the submitted form/body. */
    public function post(string $key): Input
    {
        return new Input($this->post[$key] ?? null, array_key_exists($key, $this->post));
    }

    public function allQuery(): array { return $this->get; }
    public function allPost(): array { return $this->post; }
    public function isPost(): bool { return $this->method() === 'POST'; }
    public function isGet(): bool { return $this->method() === 'GET'; }
    public function method(): string { return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET')); }
    public function path(): string
    {
        $path = parse_url((string) ($this->server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : '/';
    }
    public function ip(): ?string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? null;
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }
    public function userAgent(): string { return (string) ($this->server['HTTP_USER_AGENT'] ?? ''); }
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && is_array($this->files[$key])
            && ($this->files[$key]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }
    public function file(string $key): UploadedFile
    {
        return new UploadedFile($this->files[$key] ?? []);
    }

    /** Convenient static shortcut for tiny scripts. */
    public static function get(string $key): Input { return self::capture()->query($key); }
    public static function postValue(string $key): Input { return self::capture()->post($key); }
}
