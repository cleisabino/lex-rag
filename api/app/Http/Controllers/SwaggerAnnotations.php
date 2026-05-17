<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Lex-RAG API',
    version: '1.0.0',
    description: 'RAG system over Brazilian federal legislation. Built with Laravel 11 + PostgreSQL/pgvector + OpenAI.',
    contact: new OA\Contact(name: 'Clei Sabino', email: 'cleisabino@hotmail.com')
)]
#[OA\Server(url: 'http://localhost:8000', description: 'Local development server')]
#[OA\Tag(name: 'Documents', description: 'Document ingestion and management')]
#[OA\Tag(name: 'Query', description: 'RAG query endpoint')]
class SwaggerAnnotations {}