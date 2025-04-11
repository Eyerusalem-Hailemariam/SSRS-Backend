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
            //
            $table->foreignId('staff_id')->constrained()->onDelete('cascade')->after('id');
            $table->enum('mode', ['clock_in', 'clock_out'])->after('staff_id');
            $table->timestamp('scanned_at')->useCurrent()->after('mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            //
            $table->dropForeign(['staff_id']);
            $table->dropColumn(['staff_id', 'mode', 'scanned_at']);
        });
    }
};
