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
            $table->enum('status', ['present', 'late', 'pending', 'absent', 'early_leave', 'incomplete'])->default('pending')->after('mode');// values: 'present' or 'absent'  
            $table->integer('late_minutes')->nullable()->after('status');
        $table->integer('early_minutes')->nullable()->after('late_minutes');          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            //
        });
    }
};
