<?php

namespace App\Services\Ai;

use App\Models\Business;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Server-side Gemini adapter with function calling. The API key never leaves the server and the
 * model never receives database credentials, internal notes, or other customers' data.
 * Check the current model name and free-tier limits before configuring (see README / P9).
 */
class GeminiProvider implements ChatProvider
{
    public function respond(Business $business, string $systemPrompt, array $history, string $message, AssistantTools $tools): ProviderResult
    {
        $key = config('vendra.ai.gemini_key');
        if (! $key) {
            throw new RuntimeException('Gemini API key is not configured.');
        }
        $model = config('vendra.ai.gemini_model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $contents = [];
        foreach ($history as $turn) {
            $contents[] = ['role' => $turn['role'] === 'customer' ? 'user' : 'model', 'parts' => [['text' => $turn['text']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        $tokens = 0;
        for ($round = 0; $round <= config('vendra.ai.max_tool_rounds'); $round++) {
            $response = Http::withHeaders(['x-goog-api-key' => $key])
                ->timeout(config('vendra.ai.timeout_seconds'))
                ->post($url, [
                    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                    'contents' => $contents,
                    'tools' => [['functionDeclarations' => $tools->declarations()]],
                    'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 800],
                ]);

            if ($response->status() === 429) {
                throw new RuntimeException('Gemini quota or rate limit reached.');
            }
            $response->throw();
            $data = $response->json();
            $tokens += (int) data_get($data, 'usageMetadata.totalTokenCount', 0);
            $parts = data_get($data, 'candidates.0.content.parts', []);
            if (! $parts) {
                throw new RuntimeException('Gemini returned no content ('.data_get($data, 'candidates.0.finishReason', 'unknown').').');
            }

            $calls = array_values(array_filter($parts, fn ($p) => isset($p['functionCall'])));
            if (! $calls) {
                $text = trim(implode("\n", array_map(fn ($p) => $p['text'] ?? '', array_filter($parts, fn ($p) => empty($p['thought'])))));

                return new ProviderResult($text !== '' ? $text : 'Sorry, could you rephrase that?', $tokens);
            }

            // Return the model turn unchanged (keeps any thought signatures), then the function results.
            $contents[] = ['role' => 'model', 'parts' => $parts];
            $responses = [];
            foreach ($calls as $call) {
                $name = $call['functionCall']['name'];
                $result = $tools->execute($name, (array) ($call['functionCall']['args'] ?? []));
                $responses[] = ['functionResponse' => ['name' => $name, 'response' => ['result' => $result]]];
            }
            $contents[] = ['role' => 'user', 'parts' => $responses];
        }

        throw new RuntimeException('Too many tool rounds.');
    }
}
