<?php
declare(strict_types=1);

namespace TinyPHP\Validation;

/** Fluent rule builder for one field. Call end() to return to the Validator. */
final class Field
{
    private array $rules = [];

    public function __construct(
        private readonly Validator $validator,
        private readonly string $name,
        private readonly string $label
    ) {}

    public function required(): self { return $this->add('required', true); }
    public function nullable(): self { return $this->add('nullable', true); }
    public function string(): self { return $this->add('string', true); }
    public function integer(): self { return $this->add('integer', true); }
    public function numeric(): self { return $this->add('numeric', true); }
    public function email(): self { return $this->add('email', true); }
    public function url(): self { return $this->add('url', true); }
    public function min(int|float $value): self { return $this->add('min', $value); }
    public function max(int|float $value): self { return $this->add('max', $value); }
    public function in(array $values): self { return $this->add('in', $values); }
    public function regex(string $pattern): self { return $this->add('regex', $pattern); }
    public function same(string $otherField): self { return $this->add('same', $otherField); }

    /** Finish this field's rules and return the parent Validator for more chaining. */
    public function end(): Validator
    {
        return $this->validator->addField($this->name, $this->label, $this->rules);
    }

    private function add(string $rule, mixed $argument): self
    {
        $this->rules[$rule] = $argument;
        return $this;
    }
}
