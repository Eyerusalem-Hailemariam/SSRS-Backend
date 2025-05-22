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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_salary', 10, 2)->default(0);
            $table->integer('assigned_days')->default(0);
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('tips', 10, 2)->default(0);
            $table->decimal('net_salary_without_tips', 10, 2)->default(0);
            $table->decimal('net_salary_with_tips', 10, 2)->default(0);
            $table->timestamps();
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll');
    }
};
