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
        Schema::table('afl_api_responses', function (Blueprint $table) {
            $table->integer('response_time')->nullable()->after('response');
            $table->integer('response_code')->nullable()->after('api_response_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('afl_api_responses', function (Blueprint $table) {
            $table->dropColumn('response_time');
            $table->dropColumn('response_code');
        });
    }
};
