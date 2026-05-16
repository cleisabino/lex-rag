<?php

namespace App\Services;

class ChunkingService
{
    private int $chunkSize;
    private int $overlap;

    public function __construct(int $chunkSize = 500, int $overlap = 50)
    {
        $this->chunkSize = $chunkSize;
        $this->overlap = $overlap;
    }

    public function split(string $text): array
    {
        $paragraphs = preg_split('/\n{2,}/', trim($text));
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;

            $words = str_word_count($current . ' ' . $paragraph, 1, 'áàâãéèêíìîóòôõúùûçÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ');

            if (count($words) > $this->chunkSize && !empty($current)) {
                $chunks[] = trim($current);
                $overlapWords = array_slice($words, -$this->overlap);
                $current = implode(' ', $overlapWords) . ' ' . $paragraph;
            } else {
                $current = empty($current) ? $paragraph : $current . "\n\n" . $paragraph;
            }
        }

        if (!empty(trim($current))) {
            $chunks[] = trim($current);
        }

        return array_values(array_filter($chunks));
    }
}