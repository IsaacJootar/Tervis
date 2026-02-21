<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('administrators');

        Schema::create('administrators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->comment('Facility Administrator or LGA Officer');
            $table->string('designation')->comment('Facility Data Administrator or LGA Data Administrator');
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->onDelete('restrict')->unique();
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('restrict');
            $table->foreignId('lga_id')->nullable()->constrained('lgas')->onDelete('restrict');
            $table->unique(['state_id', 'lga_id', 'role'], 'administrators_state_lga_role_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrators');
    }
};
