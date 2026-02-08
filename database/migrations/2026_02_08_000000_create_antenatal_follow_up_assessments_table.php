<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('antenatal_follow_up_assessments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
      $table->foreignId('lga_id')->nullable()->constrained('lgas')->onDelete('set null');
      $table->foreignId('ward_id')->nullable()->constrained('wards')->onDelete('set null');
      $table->date('month_year')->comment('Month and year of record');
      $table->date('visit_date')->comment('Follow-up visit date');
      $table->string('bp')->nullable()->comment('Blood pressure');
      $table->decimal('pcv', 5, 2)->nullable()->comment('PCV percentage');
      $table->decimal('weight', 5, 2)->nullable()->comment('Weight in kg');
      $table->decimal('fundal_height', 5, 2)->nullable()->comment('Fundal height in cm');
      $table->string('presentation_position')->nullable();
      $table->string('relation_to_brim')->nullable();
      $table->unsignedSmallInteger('fetal_heart_rate')->nullable();
      $table->string('urine_test')->nullable()->comment('Alb/Sug');
      $table->string('oedema')->nullable();
      $table->text('clinical_remarks')->nullable();
      $table->text('special_delivery_instructions')->nullable();
      $table->date('next_return_date')->nullable();
      $table->boolean('xray_pelvimetry')->default(false);
      $table->string('pelvic_inlet')->nullable();
      $table->string('pelvic_cavity')->nullable();
      $table->string('pelvic_outlet')->nullable();
      $table->string('hb_genotype')->nullable();
      $table->string('rhesus')->nullable();
      $table->string('kahn_vdrl')->nullable();
      $table->text('antimalarials_therapy')->nullable();
      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('antenatal_follow_up_assessments');
  }
};
