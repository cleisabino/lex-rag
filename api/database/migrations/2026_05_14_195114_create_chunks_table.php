<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('fonte')->nullable();
            $table->integer('chunk_index');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE chunks ADD COLUMN embedding vector(1536)');
        DB::statement('CREATE INDEX ON chunks USING hnsw (embedding vector_l2_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chunks');
    }
};
