<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('postnatal_records', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade')->comment('Patient linked via DIN');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
      $table->foreignId('lga_id')->nullable()->constrained('lgas')->onDelete('set null');
      $table->foreignId('ward_id')->nullable()->constrained('wards')->onDelete('set null');
      $table->date('month_year')->comment('Month and year of record');
      $table->date('visit_date')->comment('Date of postnatal visit');
      $table->date('delivery_date')->comment('Date of delivery');
      $table->unsignedTinyInteger('days_postpartum')->nullable()->comment('Days since delivery');
      $table->string('age_range')->nullable()->comment('Age group, e.g., 20 - 24 years');
      $table->unsignedTinyInteger('parity_count')->nullable()->comment('Number of previous deliveries');
      $table->enum('attendance', ['1st Visit', '2nd Visit', '3rd Visit', 'Other'])->nullable();
      $table->text('associated_problems')->nullable()->comment('Health issues observed');
      $table->unsignedTinyInteger('mother_days')->nullable()->comment('Days postpartum for mother');
      $table->unsignedTinyInteger('child_days')->nullable()->comment('Age of child in days');
      $table->enum('child_sex', ['Male', 'Female'])->nullable();
      $table->enum('nutrition_counseling', ['Yes', 'No', 'Counseled'])->nullable();
      $table->enum('breast_examination', ['Normal', 'Abnormal', 'Not Done'])->nullable();
      $table->enum('breastfeeding_status', ['Exclusive', 'Mixed', 'Not Breastfeeding'])->nullable();
      $table->enum('family_planning', ['Counseled', 'Accepted', 'Declined'])->nullable();
      $table->enum('female_genital_mutilation', ['Yes', 'No', 'Suspected'])->nullable();
      $table->enum('vaginal_examination', ['Normal', 'Abnormal', 'Not Done'])->nullable();
      $table->string('packed_cell_volume')->nullable()->comment('PCV test result, e.g., 33%');
      $table->string('urine_test_results')->nullable()->comment('Urine test findings');
      $table->enum('newborn_care', ['Provided', 'Not Provided', 'Referred'])->nullable();
      $table->enum('kangaroo_mother_care', ['Yes', 'No', 'Not Applicable'])->nullable();
      $table->enum('visit_outcome', ['Stable', 'Referred', 'Admitted', 'Discharged'])->nullable();
      $table->unsignedSmallInteger('systolic_bp')->nullable()->comment('mmHg');
      $table->unsignedSmallInteger('diastolic_bp')->nullable()->comment('mmHg');
      $table->decimal('newborn_weight', 5, 1)->nullable()->comment('kg');
      $table->string('officer_name')->comment('Name of recording officer');
      $table->string('officer_role')->comment('Role of the recording officer, e.g., Nurse');
      $table->string('officer_designation')->comment('Officer’s role, e.g., Nurse');
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('postnatal_records');
  }
};

