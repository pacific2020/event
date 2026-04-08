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
        Schema::create('gown', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('reg_no');
            $table->datetime('expected_returning_date')->nullable();
            $table->string('status')->nullable()->default('Issued');
            $table->integer('receiver_id')->nullable();
            $table->datetime('returned_date')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('gown');
    }
};
