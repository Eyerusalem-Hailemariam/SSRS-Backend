<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('tx_ref'); // Transaction reference
            $table->decimal('amount', 10, 2); // Payment amount
            $table->string('currency')->default('ETB'); // Currency (e.g., 'ETB')
            $table->string('status'); // Payment status (e.g., 'success', 'failed')
            $table->string('email'); // User's email
            $table->string('first_name'); // User's first name
            $table->string('last_name'); // User's last name
            $table->string('phone_number'); // User's phone number
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
