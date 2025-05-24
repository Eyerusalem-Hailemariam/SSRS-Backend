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
        Schema::table('shifts', function (Blueprint $table) {
            // $table->boolean('is_overtime')->default(false)->after('end_time');
            // $table->dropColumn('overtime_type');
            $table->enum('overtime_type', ['normal', 'weekly', 'holiday', 'weekend'])->nullable()->after('is_overtime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            //
            $table->dropColumn('overtime_type');
        });
    }
};
