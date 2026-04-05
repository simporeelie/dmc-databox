<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiale extends Model
{
    protected $fillable = ['entity_id', 'name', 'slug', 'country', 'is_active'];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
