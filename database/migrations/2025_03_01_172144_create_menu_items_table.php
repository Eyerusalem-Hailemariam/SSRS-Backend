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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable(); // Changed to 'text' to store longer descriptions
            $table->string('image')->nullable()->default('default_image.jpg');
            $table->unsignedBigInteger('category_id'); // Add category_id as a foreign key
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->decimal('price', 8, 2);
            $table->decimal('total_calorie', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
