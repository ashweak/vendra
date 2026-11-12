<?php

namespace App\Services\Ai;

class ProviderResult
{
    public function __construct(public string $text, public int $tokens = 0) {}
}
