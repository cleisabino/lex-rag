# lex-rag

RAG (Retrieval-Augmented Generation) system over Brazilian federal legislation.

Built with Laravel 11 + PostgreSQL/pgvector + OpenAI.

> **Status:** 🚧 Active development. Currently in prototype phase.

## Architecture

1. **Ingestion** — PDF/text → chunks → embeddings → pgvector
2. **Retrieval** — query → embedding → similarity search
3. **Generation** — context + question → LLM → answer with sources

## Tech stack

- Laravel 11 (PHP 8.3)
- PostgreSQL + pgvector
- OpenAI API (text-embedding-3-small + gpt-4o-mini)
- Docker

## Status

- [x] RAG prototype working in Python notebook
- [x] Laravel API structure
- [x] Ingestion pipeline
- [x] Query endpoint
- [x] OpenAPI documentation

## License

MIT