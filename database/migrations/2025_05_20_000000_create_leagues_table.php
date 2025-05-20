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
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapidapi_id')->unique()->index();
            $table->string('sport_type')->index();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('type')->nullable();
            $table->json('seasons')->nullable();
            $table->json('country_details')->nullable();
            $table->json('additional_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Composite index for common queries
            $table->index(['sport_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
