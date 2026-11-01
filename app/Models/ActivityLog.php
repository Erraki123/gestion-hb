<?php

namespace App\Models;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'user_name', 'action', 'subject_type', 'subject_id',
        'subject_label', 'description', 'properties', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }

    // Journal inviolable : aucune modification ni suppression via Eloquent
    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function user() { return $this->belongsTo(User::class)->withTrashed(); }

    public function scopeForSubject(Builder $q, Model $m): Builder
    {
        return $q->where('subject_type', $m::class)->where('subject_id', $m->getKey());
    }

    public function actionLabel(): string
    {
        return ActivityLogger::ACTIONS[$this->action] ?? $this->action;
    }
}