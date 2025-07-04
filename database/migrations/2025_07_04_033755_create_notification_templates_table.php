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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Template identifier
            $table->string('type'); // notification type: flight_status_change, gate_change, etc.
            $table->string('channel'); // email, sms, push, database
            $table->string('language', 5)->default('en'); // Language code
            
            // Content fields
            $table->string('subject')->nullable(); // For email/push notifications
            $table->text('content'); // Main message content with placeholders
            $table->text('html_content')->nullable(); // HTML version for emails
            $table->json('variables')->nullable(); // Available variables for this template
            
            // Template metadata
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->foreignId('parent_template_id')->nullable()->constrained('notification_templates')->onDelete('set null');
            
            // A/B testing
            $table->string('variant')->nullable(); // A, B, C for testing
            $table->integer('usage_count')->default(0);
            $table->decimal('success_rate', 5, 2)->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'channel', 'language'], 'nt_type_channel_lang_idx');
            $table->index(['name', 'is_active'], 'nt_name_active_idx');
            $table->index(['variant', 'type'], 'nt_variant_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
