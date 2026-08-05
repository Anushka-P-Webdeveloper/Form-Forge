<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'schema', 'status',
        'ai_generated', 'previous_schema', 'user_id',
    ];

    protected $casts = [
        'schema' => 'array',
        'previous_schema' => 'array',
        'ai_generated' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (Form $form) {
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->title) . '-' . Str::lower(Str::random(6));
            }
        });
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function aiLogs()
    {
        return $this->hasMany(AiGenerationLog::class);
    }

    /**
     * Save a new schema while keeping the previous one for a one-step rollback
     * (Part D: minimal form versioning).
     */
    public function updateSchema(array $newSchema): void
    {
        $this->previous_schema = $this->schema;
        $this->schema = $newSchema;
        $this->save();
    }

    public function rollbackSchema(): bool
    {
        if (empty($this->previous_schema)) {
            return false;
        }

        $current = $this->schema;
        $this->schema = $this->previous_schema;
        $this->previous_schema = $current;
        $this->save();

        return true;
    }
}
