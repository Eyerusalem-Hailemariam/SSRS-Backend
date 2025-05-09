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
            if (Schema::hasColumn('staff_shifts', 'shift_id')) {
                
                $table->dropForeign(['shift_id']);
                
                $table->unsignedBigInteger('shift_id')->change();
            } else {
                
                $table->unsignedBigInteger('shift_id');
            }
            if (Schema::hasColumn('staff_shifts', 'staff_id')) {
                
                $table->dropForeign(['staff_id']);
               
                $table->unsignedBigInteger('staff_id')->change();
            } else {
               
                $table->unsignedBigInteger('staff_id');
            }
            
           
            $table->enum('type', ['overtime', 'regular'])->default('regular');
            
            
            $table->foreign('shift_id')->references('id')->on('shifts');
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
