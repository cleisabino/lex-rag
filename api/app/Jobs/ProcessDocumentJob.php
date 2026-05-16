<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\Chunk;
use App\Services\ChunkingService;
use App\Services\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly Document $document,
        public readonly string $text,
    ) {}

    public function handle(ChunkingService $chunking, EmbeddingService $embedding): void
    {
        $this->document->update(['status' => 'processing']);

        try {
            $chunks = $chunking->split($this->text);

            Log::info('Processing document', [
                'document_id' => $this->document->id,
                'chunks' => count($chunks),
            ]);

            foreach ($chunks as $index => $chunkText) {
                $vector = $embedding->generate($chunkText);
                $vectorSql = $embedding->vectorToSql($vector);

                DB::statement("
                    INSERT INTO chunks (document_id, content, fonte, chunk_index, embedding, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?::vector, NOW(), NOW())
                ", [
                    $this->document->id,
                    $chunkText,
                    $this->document->source,
                    $index,
                    $vectorSql,
                ]);

                if ($index > 0 && $index % 10 === 0) {
                    usleep(200000);
                }
            }

            $this->document->update(['status' => 'completed']);

            Log::info('Document processed successfully', [
                'document_id' => $this->document->id,
                'chunks_created' => count($chunks),
            ]);

        } catch (\Exception $e) {
            $this->document->update(['status' => 'failed']);
            Log::error('Document processing failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->document->update(['status' => 'failed']);
        Log::error('Job permanently failed', [
            'document_id' => $this->document->id,
            'error' => $e->getMessage(),
        ]);
    }
}