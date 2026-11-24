<?php

namespace App\Services\Ai;

class ProviderResult
{
    /** $unanswered: the assistant had no real answer (logged for the owner as an unanswered question). */
    public function __construct(public string $text, public int $tokens = 0, public bool $unanswered = false) {}
}
