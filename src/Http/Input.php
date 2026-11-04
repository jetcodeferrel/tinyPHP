<?php
declare(strict_types=1);

namespace TinyPHP\Http;

/** A small, chainable wrapper around one input value. */
final class Input
{
    public function __construct(
        private readonly mixed $input = null,
        private readonly bool $present = false
    ) {}

    public function exists(): bool { return $this->present; }
    public function isEmpty(): bool { return $this->input === null || $this->input === ''; }
    public function value(mixed $default = null): mixed { return $this->present ? $this->input : $default; }
    public function string(string $default = ''): string
    {
        return is_scalar($this->input) ? (string) $this->input : $default;
    }
    public function int(int $default = 0): int
    {
        $value = filter_var($this->input, FILTER_VALIDATE_INT);
        return $value === false ? $default : $value;
    }
    public function float(float $default = 0.0): float
    {
        return is_numeric($this->input) ? (float) $this->input : $default;
    }
    public function bool(bool $default = false): bool
    {
        $value = filter_var($this->input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $value ?? $default;
    }
    public function array(): array { return is_array($this->input) ? $this->input : []; }
    public function only(array $allowed): array
    {
        return array_intersect_key($this->array(), array_flip($allowed));
    }
    public function trim(): self
    {
        return new self(is_string($this->input) ? trim($this->input) : $this->input, $this->present);
    }
    public function lower(): self
    {
        return new self(is_string($this->input) ? mb_strtolower($this->input) : $this->input, $this->present);
    }
    public function default(mixed $default): self
    {
        return $this->isEmpty() ? new self($default, $this->present) : $this;
    }
}
