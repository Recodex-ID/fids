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
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')->constrained()->onDelete('cascade');
            $table->string('endpoint', 500);
            $table->text('p256dh_key'); // Public key for encryption
            $table->text('auth_key'); // Auth secret for encryption
            $table->string('user_agent')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['passenger_id', 'is_active']);
            $table->unique(['passenger_id', 'endpoint'], 'unique_passenger_endpoint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
