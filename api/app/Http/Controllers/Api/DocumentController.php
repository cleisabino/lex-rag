<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class DocumentController extends Controller
{
    public function __construct(private PdfService $pdf) {}

    #[OA\Get(
        path: '/api/v1/documents',
        tags: ['Documents'],
        summary: 'List all documents',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of documents'
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $documents = Document::latest()
            ->select('id', 'title', 'source', 'status', 'created_at')
            ->paginate(20);

        return response()->json($documents);
    }

    #[OA\Post(
        path: '/api/v1/documents',
        tags: ['Documents'],
        summary: 'Upload and ingest a PDF document',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['title', 'file'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', example: 'Constituição Federal 1988'),
                        new OA\Property(property: 'source', type: 'string', example: 'CF/1988'),
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 202, description: 'Document queued for processing'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'  => 'required|string|max:255',
            'source' => 'nullable|string|max:255',
            'file'   => 'required|file|mimes:pdf|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $request->file('file')->store('documents', 'local');
        $fullPath = Storage::disk('local')->path($path);
        $text = $this->pdf->extractText($fullPath);

        if (empty(trim($text))) {
            Storage::disk('local')->delete($path);
            return response()->json(['error' => 'Could not extract text from PDF.'], 422);
        }

        $document = Document::create([
            'title'  => $request->input('title'),
            'source' => $request->input('source', $request->file('file')->getClientOriginalName()),
            'status' => 'pending',
        ]);

        ProcessDocumentJob::dispatch($document, $text);

        return response()->json([
            'id'      => $document->id,
            'title'   => $document->title,
            'status'  => $document->status,
            'message' => 'Document queued for processing.',
        ], 202);
    }

    #[OA\Get(
        path: '/api/v1/documents/{id}',
        tags: ['Documents'],
        summary: 'Get document status and chunk count',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Document details'),
            new OA\Response(response: 404, description: 'Document not found'),
        ]
    )]
    public function show(Document $document): JsonResponse
    {
        return response()->json([
            'id'           => $document->id,
            'title'        => $document->title,
            'source'       => $document->source,
            'status'       => $document->status,
            'chunks_count' => $document->chunks()->count(),
            'created_at'   => $document->created_at,
        ]);
    }
}