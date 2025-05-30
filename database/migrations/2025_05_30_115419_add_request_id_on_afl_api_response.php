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
            $table->uuid('request_id')->nullable()->after('response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('afl_api_responses', function (Blueprint $table) {
            $table->dropColumn('request_id');
        });
    }
};
