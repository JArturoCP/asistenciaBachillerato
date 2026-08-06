<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Uses custom created_at timestamp column

    protected $fillable = [
        'user_id',
        'action',
        'target_table',
        'target_id',
        'ip_address',
        'details',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?string $targetTable = null, ?int $targetId = null, ?string $details = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'ip_address' => request()->ip(),
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
