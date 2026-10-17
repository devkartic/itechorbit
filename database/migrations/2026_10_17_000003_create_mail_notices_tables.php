<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_notice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('subject');
            $table->longText('body');
            $table->timestamps();
        });
        Schema::create('mail_notice_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_key')->unique();
            $table->char('request_hash', 64);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('mail_notice_templates')->nullOnDelete();
            $table->string('subject');
            $table->longText('body');
            $table->string('audience', 16);
            $table->string('mailer', 64);
            $table->string('from_address');
            $table->string('from_name');
            $table->timestamps();
        });
        Schema::create('mail_notice_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('mail_notice_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('status', 16)->default('queued');
            $table->unsignedInteger('generation')->default(1);
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('sending_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'email']);
            $table->index(['status', 'dispatched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_notice_recipients');
        Schema::dropIfExists('mail_notice_campaigns');
        Schema::dropIfExists('mail_notice_templates');
    }
};
