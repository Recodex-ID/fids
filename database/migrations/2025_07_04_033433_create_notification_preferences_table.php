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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')->constrained()->onDelete('cascade');
            
            // Notification channels
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            
            // Notification types
            $table->boolean('flight_status_changes')->default(true);
            $table->boolean('gate_changes')->default(true);
            $table->boolean('boarding_calls')->default(true);
            $table->boolean('delays')->default(true);
            $table->boolean('cancellations')->default(true);
            $table->boolean('schedule_changes')->default(true);
            $table->boolean('check_in_reminders')->default(true);
            $table->boolean('baggage_updates')->default(false);
            
            // Timing preferences
            $table->integer('boarding_call_advance_minutes')->default(30); // Minutes before boarding
            $table->integer('delay_notification_threshold')->default(15); // Minimum delay minutes to notify
            $table->time('quiet_hours_start')->nullable(); // No notifications after this time
            $table->time('quiet_hours_end')->nullable(); // No notifications before this time
            
            // Advanced preferences
            $table->enum('notification_frequency', ['immediate', 'batched', 'summary'])->default('immediate');
            $table->string('language')->default('en');
            $table->string('timezone')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['passenger_id']);
            $table->index(['email_enabled', 'sms_enabled', 'push_enabled'], 'np_channels_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
