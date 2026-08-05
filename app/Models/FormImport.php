<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_filename', 'stored_path', 'source_type', 'status',
        'detected_schema', 'field_meta', 'warnings', 'error', 'form_id', 'user_id',
    ];

    protected $casts = [
        'detected_schema' => 'array',
        'field_meta' => 'array',
        'warnings' => 'array',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
