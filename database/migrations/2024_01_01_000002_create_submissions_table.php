<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionsTable extends Migration
{
    public function up()
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('form_id')->index();
            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');

            // Answers keyed by field key, validated server-side against the
            // form's schema before being stored here.
            $table->json('data');

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Submission list is always scoped to a form and sorted by recency,
            // and CSV export/pagination both filter on form_id -> composite index.
            $table->index(['form_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('submissions');
    }
}
