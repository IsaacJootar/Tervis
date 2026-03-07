<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('prescriptions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('doctor_assessment_id')->nullable()->constrained('doctor_assessments')->nullOnDelete();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('prescribed_date');

      $table->string('drug_name', 150);
      $table->string('dosage', 120)->nullable();
      $table->string('frequency', 120)->nullable();
      $table->string('duration', 120)->nullable();
      $table->string('route', 80)->nullable();
      $table->text('instructions')->nullable();
      $table->decimal('quantity_prescribed', 10, 2)->nullable();
      $table->decimal('quantity_dispensed', 10, 2)->nullable();

      $table->string('status', 20)->default('pending');
      $table->string('prescribed_by')->nullable();
      $table->string('dispensed_by')->nullable();
      $table->date('dispensed_date')->nullable();
      $table->text('dispense_notes')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'status']);
      $table->index(['patient_id', 'status']);
      $table->index(['facility_id', 'prescribed_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('prescriptions');
  }
};