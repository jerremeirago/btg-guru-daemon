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
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->string('sport_type')->index();
            $table->unsignedBigInteger('league_id')->index();
            $table->unsignedBigInteger('team_id')->index();
            $table->integer('season')->nullable()->index();
            $table->integer('rank')->nullable();
            $table->integer('points')->nullable();
            $table->integer('goals_diff')->nullable();
            $table->string('group')->nullable();
            $table->string('form')->nullable();
            $table->string('status')->nullable();
            $table->string('description')->nullable();
            $table->json('all_stats')->nullable();
            $table->json('home_stats')->nullable();
            $table->json('away_stats')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();
            
            // Composite indices for common queries
            $table->index(['league_id', 'season']);
            $table->index(['sport_type', 'league_id']);
            $table->unique(['league_id', 'team_id', 'season']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
