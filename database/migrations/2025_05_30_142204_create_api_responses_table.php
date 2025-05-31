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
            $table->uuid('request_id')->nullable()->after('response');
            $table->string('uri', 255);
            $table->longText('response');
            $table->integer('response_time')->nullable()->after('response');
            $table->integer('response_code')->nullable()->after('api_response_time');
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
