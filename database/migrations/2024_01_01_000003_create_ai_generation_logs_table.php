<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiGenerationLogsTable extends Migration
{
    public function up()
    {
        Schema::create('ai_generation_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('form_id')->nullable()->index();
            $table->foreign('form_id')->references('id')->on('forms')->onDelete('set null');

            // 'generate' = create from prompt, 'edit' = modify existing schema
            $table->enum('type', ['generate', 'edit'])->default('generate');
            $table->text('prompt');
            $table->string('model');

            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();

            $table->unsignedTinyInteger('attempt')->default(1);
            $table->enum('status', ['success', 'repaired', 'failed'])->default('success');
            $table->text('error')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_generation_logs');
    }
}
