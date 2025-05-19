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
        Schema::create('api_requests', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('method')->default('GET');
            $table->string('sport_type')->nullable()->index();
            $table->integer('status_code')->nullable();
            $table->boolean('success')->default(true);
            $table->string('error_message')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->json('request_params')->nullable();
            $table->json('response_headers')->nullable();
            $table->timestamps();
            
            // Index for tracking API usage patterns
            $table->index(['endpoint', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_requests');
    }
};
