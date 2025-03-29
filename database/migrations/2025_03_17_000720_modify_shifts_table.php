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
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade')->after('id');
            $table->datetime('start_time')->nullable()->after('staff_id');
            $table->datetime('end_time')->nullable()->after('start_time');
            $table->boolean('is_overtime')->default(0)->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'staff_id')) {
                $table->dropForeign(['staff_id']);
                $table->dropColumn('staff_id');
            }
            if (Schema::hasColumn('shifts', 'start_time')) {
                $table->dropColumn('start_time');
            }
            if (Schema::hasColumn('shifts', 'end_time')) {
                $table->dropColumn('end_time');
            }
            if (Schema::hasColumn('shifts', 'is_overtime')) {
                $table->dropColumn('is_overtime');
            }
        });
    }
};
