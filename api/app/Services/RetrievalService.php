<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RetrievalService
{
    public function __construct(private EmbeddingService $embedding) {}

    public function search(string $query, int $topK = 5, ?int $documentId = null): array
    {
        $vector = $this->embedding->generate($query);
        $vectorSql = $this->embedding->vectorToSql($vector);

        $sql = "
            SELECT
                c.id,
                c.content,
                c.fonte,
                c.document_id,
                d.title as document_title,
                c.embedding <-> ?::vector AS distance
            FROM chunks c
            JOIN documents d ON d.id = c.document_id
            WHERE d.status = 'completed'
        ";

        $bindings = [$vectorSql];

        if ($documentId) {
            $sql .= " AND c.document_id = ?";
            $bindings[] = $documentId;
        }

        $sql .= " ORDER BY distance LIMIT ?";
        $bindings[] = $topK;

        $results = DB::select($sql, $bindings);

        return array_map(fn($r) => [
            'id'             => $r->id,
            'content'        => $r->content,
            'fonte'          => $r->fonte,
            'document_id'    => $r->document_id,
            'document_title' => $r->document_title,
            'distance'       => round($r->distance, 4),
        ], $results);
    }
}