<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Types\AflRequestType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('afl_api_responses', function (Blueprint $table) {
            $table->string('request_type')->nullable()->default(AflRequestType::Live->name);
        });       //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('afl_api_response', function (Blueprint $table) {
            $table->dropColumn('request_type');
        });       //
    }
};
