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
                $table->string('status')->nullable()->after('end_time'); // status can be: present, absent, late
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    
    }
};
