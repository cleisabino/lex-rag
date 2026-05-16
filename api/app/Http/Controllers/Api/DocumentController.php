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

class DocumentController extends Controller
{
    public function __construct(private PdfService $pdf) {}

    public function index(): JsonResponse
    {
        $documents = Document::latest()
            ->select('id', 'title', 'source', 'status', 'created_at')
            ->paginate(20);

        return response()->json($documents);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'  => 'required|string|max:255',
            'source' => 'nullable|string|max:255',
            'file'   => 'required|file|mimes:pdf|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $request->file('file')->store('documents', 'local');
        $fullPath = Storage::disk('local')->path($path);

        $text = $this->pdf->extractText($fullPath);

        if (empty(trim($text))) {
            Storage::disk('local')->delete($path);
            return response()->json([
                'error' => 'Could not extract text from PDF. File may be scanned or protected.'
            ], 422);
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