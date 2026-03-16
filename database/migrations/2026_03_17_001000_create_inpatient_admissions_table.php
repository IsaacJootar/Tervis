<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('inpatient_admissions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('bed_section_id')->nullable()->constrained('bed_sections')->nullOnDelete();
      $table->foreignId('bed_id')->constrained('beds')->onDelete('cascade');

      $table->string('admission_code', 80)->unique();
      $table->dateTime('admitted_at');
      $table->string('admitted_by', 150)->nullable();
      $table->text('admission_reason')->nullable();

      $table->string('status', 20)->default('admitted');
      $table->boolean('is_active')->default(true);
      $table->dateTime('discharged_at')->nullable();
      $table->string('discharged_by', 150)->nullable();
      $table->text('discharge_note')->nullable();
      $table->string('referral_destination', 255)->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'status']);
      $table->index(['facility_id', 'is_active']);
      $table->index(['facility_id', 'admitted_at']);
      $table->index(['patient_id', 'is_active']);
      $table->index(['bed_id', 'is_active']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('inpatient_admissions');
  }
};

