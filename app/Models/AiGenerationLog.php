<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiGenerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id', 'type', 'prompt', 'model', 'prompt_tokens',
        'completion_tokens', 'latency_ms', 'attempt', 'status', 'error',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
