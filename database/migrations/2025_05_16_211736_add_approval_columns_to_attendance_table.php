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
             Schema::table('attendance', function (Blueprint $table) {
        $table->boolean('late_approved')->default(0)->after('late_minutes');
        $table->boolean('early_approved')->default(0)->after('early_minutes');
    });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            //
            $table->dropColumn('late_approved');    
            $table->dropColumn('early_approved');
        });
    }
};
