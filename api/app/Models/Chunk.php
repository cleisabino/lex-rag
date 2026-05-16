<?php

namespace App\Models;

use Dom\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chunk extends Model
{
    protected $fillable = ['document_id', 'content', 'fonte', 'chunk_index', 'embedding'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
