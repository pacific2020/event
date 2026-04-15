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
        Schema::create('invitation_cards', function (Blueprint $table) {
            $table->id();

            // Original Fields
            $table->string('reg_no')->nullable();
            $table->string('secret_key');
            $table->string('fullname')->nullable();
            $table->string('position')->nullable();
            $table->string('email')->nullable();
            $table->string('idnumber')->nullable();
            $table->string('graduate_idnumber')->nullable();
            $table->string('phonenumber')->nullable();
            $table->string('organization')->nullable();

            $table->string('status')->nullable();
            $table->string('approval')->nullable();
            $table->string('type')->nullable(); // e.g., 'graduand' or 'parent'

            // --- New Logistics Fields ---
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('sector')->nullable();
            $table->string('cell')->nullable();
            $table->string('village')->nullable();
            $table->string('stay_overnight')->nullable(); // To store 'Yes' or 'No'

               $table->string('platenumber')->nullable(); // To store 'Yes' or 'No'
            // ----------------------------

            $table->string('scanned')->nullable();
            $table->dateTime('date_generated')->nullable();
            $table->dateTime('date_scanned')->nullable();
            $table->string('entrance_user_id')->nullable();
            $table->string('pdf')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_cards');
    }
};
