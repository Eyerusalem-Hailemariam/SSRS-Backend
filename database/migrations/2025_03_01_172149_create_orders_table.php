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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('table_id')->nullable();
            $table->string('customer_ip')->nullable(); // Stores IP address for guests
            $table->dateTime('order_date_time');
            $table->enum('order_type', ['dine-in', 'remote'])->default('dine-in'); 
            $table->decimal('total_price', 10, 2);
            $table->enum('order_status', ['pending', 'processing','ready', 'completed', 'canceled'])->default('pending');
            $table->boolean('is_paid')->default(false);  
            $table->boolean('arrived')->default(false);
            $table->string('customer_temp_id')->nullable();
            $table->boolean('is_remote')->default(false); 
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('set null');
            
            $table->foreign('table_id')
                ->references('id')
                ->on('tables')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
