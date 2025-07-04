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
        Schema::table('notifications', function (Blueprint $table) {
            // Delivery tracking fields
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'cancelled'])->default('pending')->after('sent_at');
            $table->json('delivery_channels')->nullable()->after('status'); // Which channels were used
            $table->timestamp('delivered_at')->nullable()->after('delivery_channels');
            $table->timestamp('failed_at')->nullable()->after('delivered_at');
            $table->text('failure_reason')->nullable()->after('failed_at');
            $table->integer('retry_count')->default(0)->after('failure_reason');
            $table->timestamp('retry_at')->nullable()->after('retry_count');
            
            // Enhanced metadata
            $table->string('notification_id')->nullable()->after('retry_at'); // UUID for tracking
            $table->json('metadata')->nullable()->after('notification_id'); // Additional data
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('metadata');
            
            // Template and content
            $table->string('template_name')->nullable()->after('priority');
            $table->json('template_data')->nullable()->after('template_name');
            
            // Indexes for performance
            $table->index(['status', 'created_at'], 'notif_status_created_idx');
            $table->index(['notification_id'], 'notif_id_idx');
            $table->index(['retry_at'], 'notif_retry_idx');
            $table->index(['priority', 'status'], 'notif_priority_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notif_status_created_idx');
            $table->dropIndex('notif_id_idx');
            $table->dropIndex('notif_retry_idx');
            $table->dropIndex('notif_priority_status_idx');
            
            $table->dropColumn([
                'status',
                'delivery_channels',
                'delivered_at',
                'failed_at',
                'failure_reason',
                'retry_count',
                'retry_at',
                'notification_id',
                'metadata',
                'priority',
                'template_name',
                'template_data',
            ]);
        });
    }
};
