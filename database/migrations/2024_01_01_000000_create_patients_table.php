<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('patients', function (Blueprint $table) {
      $table->id();
      $table->string('din', 8)->unique();

      $table->string('first_name', 100);
      $table->string('middle_name', 100)->nullable();
      $table->string('last_name', 100);
      $table->enum('gender', ['Male', 'Female']);
      $table->date('date_of_birth');
      $table->string('phone', 20);
      $table->string('email', 150)->nullable();

      $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
      $table->foreignId('lga_id')->nullable()->constrained('lgas')->onDelete('set null');
      $table->foreignId('ward_id')->nullable()->constrained('wards')->onDelete('set null');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

      $table->date('registration_date')->default(now());
      $table->boolean('is_active')->default(true);

      $table->boolean('is_nhis_subscriber')->default(false);
      $table->string('nhis_number', 50)->nullable();
      $table->string('nhis_provider')->nullable();
      $table->date('nhis_expiry_date')->nullable();
      $table->enum('nhis_plan_type', ['Individual', 'Family', 'Corporate'])->nullable();
      $table->string('nhis_principal_name')->nullable();
      $table->string('nhis_principal_number', 50)->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->index('din');
      $table->index('phone');
      $table->index('facility_id');
      $table->index('registration_date');
      $table->index('is_active');
      $table->index(['first_name', 'last_name']);
      $table->index(['state_id', 'lga_id', 'ward_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('patients');
  }
};
