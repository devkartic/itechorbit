<?php

namespace App\AiPro\Contracts;

use App\AiPro\Models\Site;

interface Retriever
{
    public function search(Site $site, string $question): array;
}
