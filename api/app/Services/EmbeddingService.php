<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private string $apiKey;
    private string $model;
    private string $dimensions;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.embedding_model', 'text-embedding-3-small');
        $this->dimensions = (int) config('services.openai.embedding_dimensions', 1536);
    }

    public function generate(string $text): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $text,
            ]);

        if ($response->failed()) {
            Log::error('OpenAI embedding failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Embedding generation failed: ' . $response->status());
        }

        return $response->json('data.0.embedding');
    }

    public function vectorToSql(array $embedding): string
    {
        return '[' . implode(',', $embedding) . ']';
    }
}