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
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('flight_number');
            $table->foreignId('airline_id')->constrained()->onDelete('cascade');
            $table->foreignId('origin_airport_id')->constrained('airports')->onDelete('cascade');
            $table->foreignId('destination_airport_id')->constrained('airports')->onDelete('cascade');
            $table->datetime('scheduled_departure');
            $table->datetime('scheduled_arrival');
            $table->datetime('actual_departure')->nullable();
            $table->datetime('actual_arrival')->nullable();
            $table->string('gate')->nullable();
            $table->enum('status', ['scheduled', 'boarding', 'departed', 'delayed', 'cancelled', 'arrived'])->default('scheduled');
            $table->string('aircraft_type')->nullable();
            $table->timestamps();
            
            $table->index('flight_number');
            $table->index('status');
            $table->index('scheduled_departure');
            $table->index('scheduled_arrival');
            $table->index(['airline_id', 'flight_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
