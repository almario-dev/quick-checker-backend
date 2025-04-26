<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('answer_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('name');
            $table->text('context')->nullable(); // extracted data (AI-generated)
            $table->decimal('score')->nullable(); // expected perfect score
            $table->enum('mode', ['ENFORCE_KEY', 'USE_QUESTIONNAIRE']);
            $table->json('metadata')->nullable(); // misc
            $table->timestamp('eval_at')->nullable(); // AI evaluation timestamp
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('answer_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('answer_key_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('student_name')->nullable();
            $table->decimal('score')->nullable();
            $table->text('context')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('ai_checked')->default(false); // no answer key provided, ai-based checking
            $table->timestamp('eval_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('snapshots', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachment');
            $table->text('path');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('configs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configs');
        Schema::dropIfExists('snapshots');
        Schema::dropIfExists('answer_sheets');
        Schema::dropIfExists('answer_keys');
    }
};