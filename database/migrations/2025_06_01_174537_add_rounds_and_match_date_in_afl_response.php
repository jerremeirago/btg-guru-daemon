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
            $table->string('round')->nullable()->after('response');
            // format (d.m.Y)
            $table->string('match_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('afl_api_response', function (Blueprint $table) {
            $table->dropColumn('round');
            $table->dropColumn('match_date');
        });
    }
};
