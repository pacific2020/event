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
        Schema::create('pickupplace', function (Blueprint $table) {
            $table->id();

            // 1. Use unsignedBigInteger for IDs to match the primary keys of other tables
            $table->unsignedBigInteger('college_id');

            // 2. The registration number should likely be a string
            $table->string('reg_no');

            // 3. Fixed the syntax error here: $table->string('column_name')
            // Also changed default from 'setted' to 'set' or 'pending' (standard English)
            $table->string('status')->default('set');

            $table->timestamps();

            // OPTIONAL: Add foreign key constraints for data integrity
            // This ensures college_id exists in the colleges table
            // $table->foreign('college_id')->references('id')->on('colleges')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickupplace');
    }
};
