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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')->constrained()->onDelete('cascade');
            $table->foreignId('flight_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['delay', 'gate_change', 'boarding', 'cancellation', 'arrival', 'departure']);
            $table->text('message');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index('passenger_id');
            $table->index('flight_id');
            $table->index('type');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
