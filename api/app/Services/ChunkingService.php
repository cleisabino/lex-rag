<?php

namespace App\Services;

class ChunkingService
{
    private int $maxCharsPerChunk;
    private int $overlapChars;

    public function __construct(int $maxCharsPerChunk = 2000, int $overlapChars = 200)
    {
        $this->maxCharsPerChunk = $maxCharsPerChunk;
        $this->overlapChars = $overlapChars;
    }

    public function split(string $text): array
    {
        $paragraphs = preg_split('/\n{2,}/', trim($text));
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;

            $candidate = empty($current)
                ? $paragraph
                : $current . "\n\n" . $paragraph;

            if (mb_strlen($candidate) > $this->maxCharsPerChunk && !empty($current)) {
                $chunks[] = trim($current);
                $overlap = mb_substr($current, -$this->overlapChars);
                $current = $overlap . "\n\n" . $paragraph;
            } else {
                $current = $candidate;
            }
        }

        if (!empty(trim($current))) {
            $chunks[] = trim($current);
        }

        return array_values(array_filter($chunks));
    }
}