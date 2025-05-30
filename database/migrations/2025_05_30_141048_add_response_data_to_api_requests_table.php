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
        Schema::table('api_requests', function (Blueprint $table) {
            $table->mediumText('response_data')->nullable()->after('response_headers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_requests', function (Blueprint $table) {
            $table->dropColumn('response_data');
        });
    }
};
