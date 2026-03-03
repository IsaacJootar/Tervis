<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('lab_tests', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('visit_date');

      $table->string('lab_no', 50)->nullable();
      $table->string('specimen')->nullable();
      $table->string('clinician_diagnosis')->nullable();
      $table->string('age_sex', 60)->nullable();
      $table->string('examination')->nullable();

      $table->json('report_values')->nullable();
      $table->json('widal_values')->nullable();
      $table->json('stool_values')->nullable();
      $table->json('mcs_results')->nullable();
      $table->json('urinalysis_results')->nullable();
      $table->json('microscopy_results')->nullable();
      $table->json('sensitivity_results')->nullable();

      $table->text('comment')->nullable();
      $table->string('mlt_sign')->nullable();
      $table->date('sign_date')->nullable();

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
    Schema::dropIfExists('lab_tests');
  }
};
