<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var array<string, string>
     */
    public const LABELS = [
        'login' => 'Inició sesión',
        'logout' => 'Cerró sesión',
        'created' => 'Creó',
        'updated' => 'Modificó',
        'deleted' => 'Eliminó',
    ];

    protected $fillable = [
        'actor_id',
        'actor_name',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function actionLabel(): string
    {
        return self::LABELS[$this->action] ?? $this->action;
    }

    /**
     * @return list<string>
     */
    public function changeLines(): array
    {
        $lines = [];

        foreach ($this->properties ?? [] as $attribute => $change) {
            if (! is_array($change)) {
                continue;
            }

            if (($change['changed'] ?? false) === true) {
                $lines[] = $attribute.': cambió';

                continue;
            }

            $lines[] = $attribute.': '.$this->present($change['old'] ?? null).' → '.$this->present($change['new'] ?? null);
        }

        return $lines;
    }

    private function present(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'vacío';
        }

        if (is_bool($value)) {
            return $value ? 'sí' : 'no';
        }

        if (is_array($value)) {
            return (string) json_encode($value);
        }

        return (string) $value;
    }
}
