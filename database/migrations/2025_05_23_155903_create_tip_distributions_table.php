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
    Schema::create('tip_distributions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('staff_id');
        $table->unsignedBigInteger('payment_id');
        $table->decimal('amount', 10, 2);
        $table->timestamps();

        $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tip_distributions');
    }
};
