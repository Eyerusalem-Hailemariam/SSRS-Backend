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
        Schema::table('attendance', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_shift_id')->nullable()->after('staff_id');
            $table->foreign('staff_shift_id')->references('id')->on('staff_shifts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['staff_shift_id']);
            $table->dropColumn('staff_shift_id');
        });
    }
};
