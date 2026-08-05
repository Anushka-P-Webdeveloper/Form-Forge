<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Part C — Word/Excel import.
 *
 * An import is its own row (not a Form yet) because the flow is:
 * upload -> queue parse -> preview/mapping screen -> user commits -> Form is created.
 * Keeping it separate means a botched or abandoned import never creates a
 * half-broken Form row, and we always have the original detected schema to
 * diff against what the user edited on the mapping screen.
 */
class CreateFormImportsTable extends Migration
{
    public function up()
    {
        Schema::create('form_imports', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->enum('source_type', ['docx', 'xlsx'])->index();

            // pending      -> uploaded, queued for parsing
            // processing   -> job is running
            // needs_review -> parsed, waiting on the mapping screen
            // committed    -> user confirmed, a Form row now exists
            // failed       -> parsing blew up entirely
            $table->enum('status', ['pending', 'processing', 'needs_review', 'committed', 'failed'])
                ->default('pending')
                ->index();

            // Schema exactly as produced by the deterministic parser (+ AI type
            // inference for ambiguous fields). This is what the mapping screen
            // renders and lets the user correct before anything is persisted
            // as a real Form.
            $table->json('detected_schema')->nullable();

            // Per-field metadata the mapping screen needs: was the type guessed
            // deterministically or by AI, and how confident was the guess.
            $table->json('field_meta')->nullable();

            // Blocks of the source document/sheet we couldn't confidently turn
            // into a field (e.g. free-form tables, merged cells, empty sheets).
            $table->json('warnings')->nullable();

            $table->text('error')->nullable();

            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('form_imports');
    }
}
