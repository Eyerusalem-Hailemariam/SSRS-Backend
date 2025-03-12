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
        Schema::table('tip_distributions', function (Blueprint $table) {
            //
            $table->decimal('total_tip_amount', 10, 2)->after('id');
            $table->decimal('period_start')->after('total_tip_amount');
            $table->date('period_end')->after('period_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tip_distributions', function (Blueprint $table) {
            //
            $table->dropColumn(['total_tip_amount', 'period_start', 'period_end']);
        });
    }
};
