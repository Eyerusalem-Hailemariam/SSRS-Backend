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
        Schema::table('staff_shifts', function (Blueprint $table) {
            //
            $table->date('date')->after('shift_id');
            $table->time('start_time')->nullable()->after('date'); // Can be null → use shift default
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_shifts', function (Blueprint $table) {
            //
            $table->dropColumn('date');
            $table->dropColumn('start_time');
            $table->dropColumn('end_time');
        });
    }
};
