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
        'Patient'
      ]);
      $table->string('designation', 100)->nullable();

      // Scope fields for data access control
      $table->foreignId('facility_id')->nullable()->constrained('facilities')->onDelete('set null');
      $table->foreignId('lga_id')->nullable()->constrained('lgas')->onDelete('set null');
      $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');

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
