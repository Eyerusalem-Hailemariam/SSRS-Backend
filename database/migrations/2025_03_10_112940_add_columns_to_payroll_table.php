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
        Schema::table('payroll', function (Blueprint $table) {
            $table->foreignId('staff_id')->constrained()->onDelete('cascade')->after('id');
            $table->integer('total_days_worked')->after('staff_id');
            $table->integer('overtime_days')->default(0)->after('total_days_worked');
            $table->decimal('base_pay', 10, 2)->after('overtime_days');
            $table->decimal('overtime_pay', 10, 2)->after('base_pay');
            $table->decimal('tip_share', 10, 2)->after('overtime_pay');
            $table->decimal('total_compensation', 10, 2)->after('tip_share');
            $table->date('payroll_date')->after('total_compensation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll', function (Blueprint $table) {
            //
            $table->dropForeign(['staff_id']);
            $table->dropColumn(['staff_id', 'total_days_worked', 'overtime_days', 'base_pay', 'overtime_pay', 'tip_share', 'total_compensation', 'payroll_date']);
        });
    }
};
