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
        // Modify the existing staff_shifts table
        Schema::table('staff_shifts', function (Blueprint $table) {
            
            $table->enum('type', ['overtime', 'regular'])->default('regular'); // Type of shift (overtime or regular)
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign keys and drop the columns if rolling back
       
    }
};
