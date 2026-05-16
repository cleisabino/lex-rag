<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = ['title', 'source', 'status'];

    public function chunks(): HasMany
    {
        return $this->hasMany(Chunk::class);
    }
}
