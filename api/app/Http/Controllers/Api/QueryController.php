<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GenerationService;
use App\Services\RetrievalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QueryController extends Controller
{
    public function __construct(
        private RetrievalService $retrieval,
        private GenerationService $generation,
    ) {}

    public function query(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question'    => 'required|string|min:5|max:1000',
            'top_k'       => 'nullable|integer|min:1|max:10',
            'document_id' => 'nullable|integer|exists:documents,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $question   = $request->input('question');
        $topK       = $request->input('top_k', 5);
        $documentId = $request->input('document_id');

        $chunks = $this->retrieval->search($question, $topK, $documentId);

        if (empty($chunks)) {
            return response()->json([
                'question' => $question,
                'answer'   => 'Não encontrei documentos indexados para responder essa pergunta.',
                'sources'  => [],
                'chunks'   => [],
            ]);
        }

        $result = $this->generation->generate($question, $chunks);

        return response()->json([
            'question' => $question,
            'answer'   => $result['answer'],
            'model'    => $result['model'],
            'sources'  => collect($chunks)->pluck('fonte')->unique()->values(),
            'chunks'   => collect($chunks)->map(fn($c) => [
                'fonte'    => $c['fonte'],
                'distance' => $c['distance'],
                'preview'  => mb_substr($c['content'], 0, 150) . '...',
            ]),
            'usage'    => $result['usage'],
        ]);
    }
}