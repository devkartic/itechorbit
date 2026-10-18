<?php

namespace App\AiPro\Contracts;

interface AiProvider
{
    /** @return array<int, array<float>> */
    public function embed(array $texts, string $purpose = 'document'): array;

    /** @return array{answer: string, source_ids: array, usage: array} */
    public function answer(string $question, array $context, array $history): array;
}
