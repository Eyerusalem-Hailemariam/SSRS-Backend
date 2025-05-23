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
        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->boolean('is_night_shift')->default(false)->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->dropColumn('is_night_shift');
        });
    }
};
