<?php

namespace App\Exceptions;

use RuntimeException;

/** A business-rule failure with a safe, user-facing message (no private details). */
class RuleViolation extends RuntimeException
{
    public function __construct(string $message, public readonly string $code_ = 'rule', public readonly array $context = [])
    {
        parent::__construct($message);
    }
}
