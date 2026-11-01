<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogger;
use Illuminate\Support\Str;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($m) {
            $m->logModel('created', 'Création', ['after' => $m->loggableAttributes($m->getAttributes())]);
        });

        static::updated(function ($m) {
            $changes = $m->loggableAttributes($m->getChanges());

            if (! $changes) {
                return; // ex. restore() : seul deleted_at change
            }

            $before = [];
            foreach (array_keys($changes) as $key) {
                $before[$key] = $m->getOriginal($key);
            }

            $m->logModel('updated', 'Modification', ['before' => $before, 'after' => $changes], array_keys($changes));
        });

        static::deleted(function ($m) {
            $forced = method_exists($m, 'isForceDeleting') && $m->isForceDeleting();

            $m->logModel(
                'deleted',
                $forced ? 'Suppression définitive' : 'Suppression',
                ['before' => $m->loggableAttributes($m->getOriginal())]
            );
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(fn ($m) => $m->logModel('restored', 'Restauration'));
        }
    }

    public function logModel(string $action, string $verb, array $properties = [], array $fields = []): void
    {
        $kind  = ActivityLogger::SUBJECTS[static::class] ?? class_basename($this);
        $label = ActivityLogger::labelFor($this);
        $extra = $fields ? ' (' . Str::limit(implode(', ', $fields), 120, '…') . ')' : '';

        ActivityLogger::log($action, "{$verb} — {$kind} « {$label} »{$extra}", $this, $properties);
    }

    public function loggableAttributes(array $attributes): array
    {
        $exclude = array_merge(
            ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token', 'email_verified_at'],
            property_exists($this, 'logExclude') ? $this->logExclude : []
        );

        return array_diff_key($attributes, array_flip($exclude));
    }
}