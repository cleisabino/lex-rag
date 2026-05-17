<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GenerationService;
use App\Services\RetrievalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class QueryController extends Controller
{
    public function __construct(
        private RetrievalService $retrieval,
        private GenerationService $generation,
    ) {}

    #[OA\Post(
        path: '/api/v1/query',
        tags: ['Query'],
        summary: 'Query the RAG system',
        description: 'Receives a question in natural language, retrieves relevant chunks via vector similarity search, and generates an answer grounded in the indexed documents.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['question'],
                properties: [
                    new OA\Property(property: 'question', type: 'string', minLength: 5, maxLength: 1000,
                        example: 'Quais são os direitos fundamentais garantidos pela Constituição?'),
                    new OA\Property(property: 'top_k', type: 'integer', minimum: 1, maximum: 10, default: 5),
                    new OA\Property(property: 'document_id', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'RAG response with sources'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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