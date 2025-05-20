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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapidapi_id')->unique()->index();
            $table->string('sport_type')->index();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->string('name');
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->integer('age')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->boolean('injured')->default(false);
            $table->string('photo_url')->nullable();
            $table->json('birth_details')->nullable();
            $table->json('statistics')->nullable();
            $table->json('additional_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Composite indices for common queries
            $table->index(['sport_type', 'is_active']);
            $table->index(['team_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
