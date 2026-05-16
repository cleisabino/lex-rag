<?php

namespace App\Services;

class PdfService
{
    public function extractText(string $filePath): string
    {
        $output = [];
        $returnCode = 0;

        exec(
            sprintf('pdftotext %s - 2>/dev/null', escapeshellarg($filePath)),
            $output,
            $returnCode
        );

        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to extract text from PDF.');
        }

        $text = implode("\n", $output);
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        $text = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);

        // Normaliza múltiplos espaços mas preserva quebras de linha
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}