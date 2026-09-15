<?php

namespace App\Exceptions;

use RuntimeException;

class BusinessException extends RuntimeException
{
    public function __construct(string $message, private readonly string $field = 'general')
    {
        parent::__construct($message);
    }

    public function getField(): string
    {
        return $this->field;
    }
}
