<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->string('category');
            $table->string('event_name');
            $table->dateTime('starting_date');
            $table->dateTime('ending_date');
            $table->integer('expected_invitation')->default(0);
            $table->dateTime('generated_at')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::rename('events');
    }
};
