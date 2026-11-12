<?php

namespace App\Services\Ai;

use App\Models\Business;

interface ChatProvider
{
    /**
     * @param  array<int, array{role: string, text: string}>  $history  previous customer-visible turns (oldest first)
     */
    public function respond(Business $business, string $systemPrompt, array $history, string $message, AssistantTools $tools): ProviderResult;
}
