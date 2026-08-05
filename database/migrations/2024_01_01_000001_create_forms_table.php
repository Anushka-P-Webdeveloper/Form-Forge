<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormsTable extends Migration
{
    public function up()
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // Unique public slug used for the /forms/{slug}/fill URL. Indexed because
            // every public fill request and submission lookup hits this column.
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Single source of truth for the form structure. Canvas UI and raw JSON
            // editor both read/write here; server-side submission validation is
            // derived from this at request time (never trust the client copy).
            $table->json('schema');

            // draft = being edited, published = accepting public submissions,
            // generating = an AI job is currently building/editing this form.
            $table->enum('status', ['draft', 'published', 'generating', 'failed'])
                ->default('draft')
                ->index();

            $table->boolean('ai_generated')->default(false);

            // Lightweight versioning: previous schema kept so we can roll back
            // the last change (Part D - form versioning, deliberately minimal scope).
            $table->json('previous_schema')->nullable();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('forms');
    }
}
