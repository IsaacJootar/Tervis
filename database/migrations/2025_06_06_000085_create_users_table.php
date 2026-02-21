<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('users', function (Blueprint $table) {
      $table->id();
      $table->string('first_name', 100);
      $table->string('last_name', 100);
      $table->string('username', 100)->unique();
      $table->string('email', 150)->unique();
      $table->string('phone', 20)->nullable();
      $table->string('password');
      $table->enum('role', [
        'Central Admin',
        'State Data Administrator',
        'State Administrator',
        'LGA Officer',
        'LGA Data Administrator',
        'LGA Administrator',
        'Facility Administrator',
        'Data Officer',
        'Verification Officer',
        'Patient'
      ]);
      $table->string('designation', 100)->nullable();

      // Scope fields for data access control
      $table->unsignedBigInteger('facility_id')->nullable();
      $table->unsignedBigInteger('lga_id')->nullable();
      $table->unsignedBigInteger('state_id')->nullable();

      $table->boolean('is_active')->default(true);
      $table->rememberToken();
      $table->timestamps();
      $table->softDeletes();

      $table->index('email');
      $table->index('role');
      $table->index('facility_id');
      $table->index('lga_id');
      $table->index('state_id');
      $table->index('is_active');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('users');
  }
};
