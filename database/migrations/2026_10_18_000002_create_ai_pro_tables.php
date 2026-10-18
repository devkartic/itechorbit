<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_pro_sites', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 64)->unique();
            $table->string('name');
            $table->string('url', 2048);
            $table->json('origins');
            $table->boolean('is_local')->default(false);
            $table->boolean('enabled')->default(false);
            $table->boolean('resync_requested')->default(false);
            $table->string('welcome')->default('Hi! Ask me about this website.');
            $table->string('color', 7)->default('#2563eb');
            $table->string('contact_url', 2048)->nullable();
            $table->unsignedInteger('minute_limit')->default(10);
            $table->unsignedInteger('daily_limit')->default(500);
            $table->timestamps();
        });
        Schema::create('ai_pro_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('ai_pro_sites')->cascadeOnDelete();
            $table->string('source_key', 64);
            $table->string('kind', 20);
            $table->string('title');
            $table->string('url', 2048)->nullable();
            $table->longText('content');
            $table->string('checksum', 64);
            $table->boolean('enabled')->default(true);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['site_id', 'source_key']);
        });
        Schema::create('ai_pro_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('ai_pro_sites')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('ai_pro_sources')->cascadeOnDelete();
            $table->text('content');
            $table->json('embedding');
            $table->string('embedding_model');
            $table->timestamps();
        });
        Schema::create('ai_pro_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('ai_pro_sites')->cascadeOnDelete();
            $table->string('public_id', 64)->unique();
            $table->string('token_hash', 64);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
        Schema::create('ai_pro_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('ai_pro_sites')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('ai_pro_conversations')->cascadeOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->json('sources')->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('usage')->nullable();
            $table->timestamps();
            $table->index(['site_id', 'created_at']);
        });
        Schema::create('ai_pro_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('ai_pro_sites')->cascadeOnDelete();
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('processed')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['sync_runs', 'messages', 'conversations', 'chunks', 'sources', 'sites'] as $table) {
            Schema::dropIfExists('ai_pro_'.$table);
        }
    }
};
