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
    Schema::create('family_planning_registrations', function (Blueprint $table) {
      $table->id();

      // Foreign Keys
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

      // Registration Information
      $table->date('registration_date');
      $table->string('client_reg_number', 50)->nullable();
      $table->enum('referral_source', ['Self', 'PHC', 'Hospital', 'NGO', 'Private', 'Other'])->nullable();

      // Obstetric History
      $table->integer('children_born_alive')->nullable();
      $table->integer('children_still_living')->nullable();
      $table->integer('miscarriages_stillbirths_abortions')->nullable();
      $table->date('last_pregnancy_ended')->nullable();
      $table->enum('last_pregnancy_result', ['Live Birth', 'Stillbirth', 'Miscarriage', 'Abortion'])->nullable();
      $table->boolean('breastfeeding')->nullable();
      $table->enum('want_more_children', ['Yes', 'No', 'Undecided'])->nullable();

      // Menstrual History
      $table->date('last_menstrual_period')->nullable();
      $table->enum('menstrual_cycle', ['Regular', 'Irregular'])->nullable();
      $table->integer('cycle_duration')->nullable(); // in days

      // Medical History
      $table->json('medical_conditions')->nullable(); // Array of conditions
      $table->string('other_illness_specify')->nullable();
      $table->boolean('smoke')->nullable();
      $table->enum('last_pregnancy_complication', ['Normal', 'Complicated'])->nullable();
      $table->text('complication_specify')->nullable();

      // Contraceptive History
      $table->boolean('prior_contraceptive')->nullable();
      $table->string('prior_method')->nullable();

      // Contraceptive Method Selected
      $table->string('contraceptive_selected')->nullable();
      $table->string('brand_size_model')->nullable();
      $table->enum('source', ['Free (Government)', 'Subsidized', 'Full Price'])->nullable();
      $table->enum('quality', ['Accepted', 'Continuing', 'Switching'])->nullable();

      // Physical Examination
      $table->decimal('weight', 5, 2)->nullable(); // kg
      $table->string('blood_pressure', 20)->nullable(); // e.g., 120/80
      $table->enum('breasts', ['Normal', 'Abnormal'])->nullable();
      $table->enum('uterus_position', ['Anteverted', 'Retroverted', 'Midposition'])->nullable();
      $table->enum('uterus_size', ['Normal', 'Enlarged'])->nullable();
      $table->boolean('cervix_tears')->nullable();
      $table->boolean('cervix_erosion')->nullable();
      $table->boolean('vaginal_discharge')->nullable();
      $table->string('discharge_colour')->nullable();
      $table->string('discharge_odor')->nullable();
      $table->boolean('cervix_discharge')->nullable();
      $table->boolean('liver_enlarged')->nullable();
      $table->text('laboratory_results')->nullable();
      $table->text('other_observations')->nullable();

      // Follow-up & Scheduling
      $table->date('next_appointment')->nullable();

      // Pregnancy Tracking (After Initial Visit)
      // Pregnancy 1
      $table->date('pregnancy1_date_ended')->nullable();
      $table->string('pregnancy1_outcome')->nullable(); // Live Birth, Miscarriage, Stillbirth, Live Birth died later
      $table->string('pregnancy1_complication')->nullable();

      // Pregnancy 2
      $table->date('pregnancy2_date_ended')->nullable();
      $table->string('pregnancy2_outcome')->nullable();
      $table->string('pregnancy2_complication')->nullable();

      // Officer Information
      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();

      // Indexes
      $table->index('patient_id');
      $table->index('facility_id');
      $table->index('registration_date');
      $table->index('client_reg_number');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('family_planning_registrations');
  }
};
