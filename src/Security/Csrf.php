<?php
declare(strict_types=1);

namespace TinyPHP\Security;

final class Csrf
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Start the session before requesting a CSRF token.');
        }
        if (empty($_SESSION['_tinyphp_csrf'])) {
            $_SESSION['_tinyphp_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_tinyphp_csrf'];
    }

    public static function verify(?string $token): bool
    {
        return is_string($token) && $token !== ''
            && session_status() === PHP_SESSION_ACTIVE
            && isset($_SESSION['_tinyphp_csrf'])
            && hash_equals((string) $_SESSION['_tinyphp_csrf'], $token);
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['_tinyphp_csrf']);
        }
    }

    private function __construct() {}
}
