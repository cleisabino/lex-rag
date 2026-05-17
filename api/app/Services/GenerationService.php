<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerationService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model  = config('services.openai.chat_model', 'gpt-4o-mini');
    }

    public function generate(string $question, array $chunks): array
    {
        $context = collect($chunks)
            ->map(fn($c) => "Fonte: {$c['fonte']}\n{$c['content']}")
            ->join("\n\n---\n\n");

        $prompt = <<<PROMPT
            Você é um assistente jurídico especializado na legislação brasileira.
            Responda a pergunta abaixo usando APENAS as informações dos trechos fornecidos.
            Se a resposta não estiver nos trechos, diga exatamente: "Não encontrei informações sobre isso nos documentos indexados."
            Nunca invente informações. Cite as fontes ao final da resposta.

            Trechos relevantes:
            {$context}

            Pergunta: {$question}

            Resposta:
            PROMPT;

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $this->model,
                'temperature' => 0,
                'messages'    => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            Log::error('OpenAI generation failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Generation failed: ' . $response->status());
        }

        return [
            'answer' => $response->json('choices.0.message.content'),
            'model'  => $this->model,
            'usage'  => $response->json('usage'),
        ];
    }
}