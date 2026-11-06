<?php
declare(strict_types=1);

namespace TinyPHP\Validation\Exceptions;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(private readonly array $validationErrors)
    {
        parent::__construct('The submitted data failed validation.');
    }

    public function errors(): array
    {
        return $this->validationErrors;
    }
}
