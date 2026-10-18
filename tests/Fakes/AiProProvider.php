<?php

namespace Tests\Fakes;

use App\AiPro\Contracts\AiProvider;
use RuntimeException;

class AiProProvider implements AiProvider
{
    public int $embedCalls = 0;

    public int $answerCalls = 0;

    public bool $fail = false;

    public ?array $citations = null;

    public array $context = [];

    public array $history = [];

    public function embed(array $texts, string $purpose = 'document'): array
    {
        $this->embedCalls++;
        if ($this->fail) {
            throw new RuntimeException('Synthetic provider failure');
        }

        return array_map(fn () => [1.0, 0.0, 0.0], $texts);
    }

    public function answer(string $question, array $context, array $history): array
    {
        $this->answerCalls++;
        if ($this->fail) {
            throw new RuntimeException('Synthetic provider failure');
        }
        $this->context = $context;
        $this->history = $history;

        return ['answer' => preg_match('/[\x{0980}-\x{09FF}]/u', $question) ? 'ওয়েব অ্যাপ্লিকেশন তৈরি করা হয়।' : 'We build web applications.',
            'source_ids' => $this->citations ?? [$context[0]['id']], 'usage' => ['input_tokens' => 100, 'output_tokens' => 20]];
    }
}
