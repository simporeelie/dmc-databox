<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequest extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'category', 'period',
        'status', 'handled_by', 'response', 'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'pending'     => 'En attente',
            'in_progress' => 'En cours',
            'fulfilled'   => 'Traité',
            'closed'      => 'Fermé',
            default       => $this->status,
        };
    }
}
