<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (!Schema::hasColumn('shifts', 'end_date')) {
                $table->date('end_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'start_date')) {
                $table->dropColumn('start_date');
            }
            if (Schema::hasColumn('shifts', 'end_date')) {
                $table->dropColumn('end_date');
            }
        });
    }
};

