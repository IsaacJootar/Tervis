<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('antenatal_registrations', function (Blueprint $table) {
      $table->id();

      // Patient and Facility Links
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

      // Multiple Pregnancy Tracking
      $table->integer('pregnancy_number')->default(1);
      $table->boolean('is_active')->default(true);
      $table->enum('pregnancy_status', [
        'active',
        'delivered',
        'miscarriage',
        'stillbirth',
        'transferred',
        'ongoing'
      ])->default('active');
      $table->foreignId('previous_registration_id')->nullable()->constrained('antenatal_registrations')->onDelete('set null');

      // Registration Details
      $table->date('registration_date');
      $table->date('date_of_booking');
      $table->string('indication_for_booking')->nullable();

      // Biographical Information (Pregnancy-Specific)
      $table->string('xray_no')->nullable();
      $table->string('unit_no')->nullable();
      $table->string('ethnic_group')->nullable();
      $table->string('occupation')->nullable();
      $table->boolean('speaks_english')->default(true);
      $table->boolean('literate')->default(true);
      $table->text('special_points')->nullable();
      $table->string('consultant')->nullable();

      // Husband/Partner Information
      $table->string('husband_name')->nullable();
      $table->string('husband_occupation')->nullable();
      $table->string('husband_employer')->nullable();

      // Current Pregnancy Details
      $table->date('lmp');
      $table->date('edd');
      $table->integer('gestational_age_weeks')->nullable();
      $table->integer('gestational_age_days')->nullable();
      $table->enum('booking_trimester', ['First', 'Second', 'Third'])->nullable();

      // Obstetric History
      $table->integer('gravida')->nullable();
      $table->integer('parity')->nullable();
      $table->integer('total_births')->nullable();
      $table->integer('living_children')->nullable();
      $table->integer('abortions')->nullable();

      // Previous Pregnancy History (up to 5 pregnancies)
      // Pregnancy 0
      $table->date('preg_0_dob')->nullable();
      $table->string('preg_0_dur')->nullable();
      $table->enum('preg_0_outcome', ['Normal', 'Complicated', 'Cesarean', 'Stillbirth', 'Miscarriage', 'Other'])->nullable();
      $table->string('preg_0_weight')->nullable();
      $table->string('preg_0_nndd')->nullable();

      // Pregnancy 1
      $table->date('preg_1_dob')->nullable();
      $table->string('preg_1_dur')->nullable();
      $table->enum('preg_1_outcome', ['Normal', 'Complicated', 'Cesarean', 'Stillbirth', 'Miscarriage', 'Other'])->nullable();
      $table->string('preg_1_weight')->nullable();
      $table->string('preg_1_nndd')->nullable();

      // Pregnancy 2
      $table->date('preg_2_dob')->nullable();
      $table->string('preg_2_dur')->nullable();
      $table->enum('preg_2_outcome', ['Normal', 'Complicated', 'Cesarean', 'Stillbirth', 'Miscarriage', 'Other'])->nullable();
      $table->string('preg_2_weight')->nullable();
      $table->string('preg_2_nndd')->nullable();

      // Pregnancy 3
      $table->date('preg_3_dob')->nullable();
      $table->string('preg_3_dur')->nullable();
      $table->enum('preg_3_outcome', ['Normal', 'Complicated', 'Cesarean', 'Stillbirth', 'Miscarriage', 'Other'])->nullable();
      $table->string('preg_3_weight')->nullable();
      $table->string('preg_3_nndd')->nullable();

      // Pregnancy 4
      $table->date('preg_4_dob')->nullable();
      $table->string('preg_4_dur')->nullable();
      $table->enum('preg_4_outcome', ['Normal', 'Complicated', 'Cesarean', 'Stillbirth', 'Miscarriage', 'Other'])->nullable();
      $table->string('preg_4_weight')->nullable();
      $table->string('preg_4_nndd')->nullable();

      // Medical History
      $table->boolean('heart_disease')->default(false);
      $table->boolean('chest_disease')->default(false);
      $table->boolean('kidney_disease')->default(false);
      $table->boolean('blood_transfusion')->default(false);
      $table->text('other_medical_history')->nullable();

      // Family History
      $table->boolean('family_multiple_pregnancy')->default(false);
      $table->boolean('family_tuberculosis')->default(false);
      $table->boolean('family_hypertension')->default(false);
      $table->boolean('family_heart_disease')->default(false);
      $table->text('other_family_history')->nullable();

      // Current Symptoms
      $table->boolean('bleeding')->default(false);
      $table->boolean('discharge')->default(false);
      $table->boolean('urinary_symptoms')->default(false);
      $table->boolean('swelling_ankles')->default(false);
      $table->text('other_symptoms')->nullable();

      // Physical Examination
      $table->decimal('height', 5, 1)->nullable();
      $table->decimal('weight', 5, 1)->nullable();
      $table->string('blood_pressure')->nullable();
      $table->decimal('hemoglobin', 4, 1)->nullable();
      $table->enum('genotype', ['AA', 'AS', 'AC', 'SS', 'SC', 'CC'])->nullable();
      $table->enum('blood_group_rhesus', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
      $table->enum('kahn_test', ['Positive', 'Negative'])->nullable();
      $table->boolean('oedema')->default(false);
      $table->boolean('anaemia')->default(false);
      $table->enum('breast_nipple', ['Normal', 'Abnormal'])->nullable();
      $table->enum('chest_xray', ['Normal', 'Abnormal'])->nullable();
      $table->string('urine_analysis')->nullable();
      $table->text('respiratory_system')->nullable();

      // Additional Information
      $table->text('comments')->nullable();
      $table->text('examiner')->nullable();
      $table->text('special_instructions')->nullable();

      // Officer Tracking
      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();
      $table->softDeletes();

      // Indexes for performance
      $table->index(['patient_id', 'is_active']);
      $table->index(['facility_id', 'registration_date']);
      $table->index('pregnancy_status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('antenatal_registrations');
  }
};
