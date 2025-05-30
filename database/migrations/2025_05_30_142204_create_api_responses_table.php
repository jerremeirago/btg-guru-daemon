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
        Schema::create('afl_api_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uri', 255);
            $table->longText('response');
            $table->timestamps();

            $table->index('uri');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('afl_api_responses');
    }
};
