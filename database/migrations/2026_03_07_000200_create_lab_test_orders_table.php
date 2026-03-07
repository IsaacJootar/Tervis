<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('lab_test_orders', function (Blueprint $table) {
      $table->id();
      $table->foreignId('doctor_assessment_id')->nullable()->constrained('doctor_assessments')->nullOnDelete();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('visit_date');

      $table->string('test_name', 150);
      $table->string('specimen')->nullable();
      $table->string('priority', 20)->default('Routine');
      $table->text('instructions')->nullable();

      $table->string('status', 20)->default('pending');
      $table->string('requested_by')->nullable();
      $table->timestamp('requested_at')->nullable();

      $table->foreignId('completed_lab_test_id')->nullable()->constrained('lab_tests')->nullOnDelete();
      $table->timestamp('completed_at')->nullable();
      $table->string('completed_by')->nullable();
      $table->text('completion_notes')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'status']);
      $table->index(['patient_id', 'status']);
      $table->index(['facility_id', 'visit_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('lab_test_orders');
  }
};