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
        Schema::table('staff', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('email')->unique()->after('name');
            $table->string('password')->after('email');
            $table->string('role')->after('password');
            $table->decimal('total_salary', 10, 2)->after('role');
            $table->decimal('overtime_rate', 10, 2)->after('total_salary');
            $table->decimal('tips', 10, 2)->default(0)->after('overtime_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['name', 'email', 'password', 'role', 'total_salary', 'overtime_rate', 'tips']);
        });
    }
};
