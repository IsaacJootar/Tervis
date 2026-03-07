<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('doctor_assessments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('doctor_user_id')->nullable()->constrained('users')->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('visit_date');

      $table->text('chief_complaints')->nullable();
      $table->longText('history_of_present_illness')->nullable();
      $table->text('vital_signs')->nullable();
      $table->longText('physical_examination')->nullable();
      $table->longText('clinical_findings')->nullable();
      $table->string('provisional_diagnosis')->nullable();
      $table->string('final_diagnosis')->nullable();

      $table->longText('assessment_note')->nullable();
      $table->longText('management_plan')->nullable();
      $table->longText('follow_up_instructions')->nullable();
      $table->longText('referral_note')->nullable();
      $table->longText('advice_to_patient')->nullable();

      $table->boolean('requires_lab_tests')->default(false);
      $table->boolean('requires_drugs')->default(false);
      $table->json('summary_map')->nullable();

      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'month_year']);
      $table->index(['facility_id', 'visit_date']);
      $table->index(['patient_id', 'facility_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('doctor_assessments');
  }
};