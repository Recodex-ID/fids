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
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->foreignId('flight_id')->constrained()->onDelete('cascade');
            $table->string('seat_number')->nullable();
            $table->string('booking_reference')->unique();
            $table->timestamps();
            
            $table->index('email');
            $table->index('booking_reference');
            $table->index('flight_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passengers');
    }
};
