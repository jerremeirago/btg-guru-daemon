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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapidapi_id')->unique()->index();
            $table->string('sport_type')->index();
            $table->unsignedBigInteger('league_id')->nullable()->index();
            $table->unsignedBigInteger('home_team_id')->nullable()->index();
            $table->unsignedBigInteger('away_team_id')->nullable()->index();
            $table->string('status_short')->nullable()->index();
            $table->string('status_long')->nullable();
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->dateTime('match_date')->nullable()->index();
            $table->bigInteger('timestamp')->nullable();
            $table->string('timezone')->nullable();
            $table->string('venue_name')->nullable();
            $table->string('venue_city')->nullable();
            $table->json('league_details')->nullable();
            $table->json('teams')->nullable();
            $table->json('scores')->nullable();
            $table->json('fixture')->nullable();
            $table->json('additional_data')->nullable();
            $table->boolean('has_updates')->default(false)->index();
            $table->timestamps();
            
            // Composite indices for common queries
            $table->index(['sport_type', 'status_short']);
            $table->index(['sport_type', 'match_date']);
            $table->index(['league_id', 'match_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
