<?php
declare(strict_types=1);

namespace TinyPHP\Validation;

use TinyPHP\Validation\Exceptions\ValidationException;

/**
 * Small, chainable validation engine.
 *
 * Example:
 * Validator::make($data)
 *     ->field('email')->required()->email()->end()
 *     ->passes();
 */
final class Validator
{
    /** @var array<string, array{label:string,rules:array}> */
    private array $fields = [];

    /** @var array<string, list<string>> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $validated = [];

    private bool $hasRun = false;

    private function __construct(private readonly array $data) {}

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function field(string $name, ?string $label = null): Field
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Validation field name cannot be empty.');
        }

        return new Field($this, $name, $label ?? self::humanize($name));
    }

    /** @internal Used by Field::end(). */
    public function addField(string $name, string $label, array $rules): self
    {
        $this->fields[$name] = ['label' => $label, 'rules' => $rules];
        $this->hasRun = false;
        return $this;
    }

    public function passes(): bool
    {
        $this->run();
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        $this->run();
        return $this->errors;
    }

    /** @return list<string> */
    public function errorsFor(string $field): array
    {
        $this->run();
        return $this->errors[$field] ?? [];
    }

    /**
     * Return only fields that were configured and passed validation.
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        $this->run();
        return $this->validated;
    }

    /**
     * Return validated data or throw an exception containing the errors.
     *
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }

        return $this->validated;
    }

    private function run(): void
    {
        if ($this->hasRun) {
            return;
        }

        $this->errors = [];
        $this->validated = [];

        foreach ($this->fields as $name => $definition) {
            $exists = array_key_exists($name, $this->data);
            $value = $exists ? $this->data[$name] : null;
            $rules = $definition['rules'];
            $label = $definition['label'];

            $required = isset($rules['required']);
            $nullable = isset($rules['nullable']);

            if ($required && self::isEmptyValue($value)) {
                $this->addError($name, "{$label} is required.");
                continue;
            }

            // Missing optional fields and explicitly nullable empty values skip other rules.
            if (!$exists || ($nullable && self::isEmptyValue($value))) {
                continue;
            }

            $fieldPassed = true;

            $numericMeasurement = isset($rules['integer']) || isset($rules['numeric']);

            foreach ($rules as $rule => $argument) {
                if (in_array($rule, ['required', 'nullable'], true)) {
                    continue;
                }

                $message = $this->checkRule($rule, $argument, $value, $name, $label, $numericMeasurement);
                if ($message !== null) {
                    $this->addError($name, $message);
                    $fieldPassed = false;
                }
            }

            if ($fieldPassed) {
                $this->validated[$name] = $value;
            }
        }

        $this->hasRun = true;
    }

    private function checkRule(
        string $rule,
        mixed $argument,
        mixed $value,
        string $name,
        string $label,
        bool $numericMeasurement
    ): ?string {
        $valid = match ($rule) {
            'string' => is_string($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'numeric' => is_numeric($value),
            'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false,
            'min' => self::measure($value, $numericMeasurement) !== null
                && self::measure($value, $numericMeasurement) >= (float) $argument,
            'max' => self::measure($value, $numericMeasurement) !== null
                && self::measure($value, $numericMeasurement) <= (float) $argument,
            'in' => is_array($argument) && in_array($value, $argument, true),
            'regex' => is_string($value) && @preg_match((string) $argument, $value) === 1,
            'same' => array_key_exists((string) $argument, $this->data)
                && $value === $this->data[(string) $argument],
            default => throw new \LogicException("Unknown validation rule: {$rule}"),
        };

        if ($valid) {
            return null;
        }

        return match ($rule) {
            'string' => "{$label} must be a string.",
            'integer' => "{$label} must be an integer.",
            'numeric' => "{$label} must be numeric.",
            'email' => "{$label} must be a valid email address.",
            'url' => "{$label} must be a valid URL.",
            'min' => "{$label} must be at least {$argument}.",
            'max' => "{$label} may not be greater than {$argument}.",
            'in' => "{$label} contains an unsupported value.",
            'regex' => "{$label} has an invalid format.",
            'same' => "{$label} must match " . self::humanize((string) $argument) . '.',
            default => "{$label} is invalid.",
        };
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private static function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }

    private static function measure(mixed $value, bool $numeric = false): ?float
    {
        if ($numeric && is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            return function_exists('mb_strlen') ? (float) mb_strlen($value) : (float) strlen($value);
        }
        if (is_array($value)) {
            return (float) count($value);
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        return null;
    }

    private static function humanize(string $value): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $value));
    }
}
