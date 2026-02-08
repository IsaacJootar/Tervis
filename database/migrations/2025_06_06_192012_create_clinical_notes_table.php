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
    Schema::create('clinical_notes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Patient linked via DIN');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
      $table->foreignId('lga_id')->nullable()->constrained('lgas')->onDelete('set null');
      $table->foreignId('ward_id')->nullable()->constrained('wards')->onDelete('set null');
      $table->date('month_year')->comment('Month and year of record');
      $table->date('date_of_visit')->comment('Date of visit for lab test or clinical note');
      $table->enum('section', [
        'Blood Test',
        'Urine Test',
        'Ultrasound',
        'Clinical Note',
        'X-Ray',
        'ECG',
        'Other Lab Tests'
      ])->comment('Type of test or note section');
      $table->text('note')->comment('Lab test results, clinical observations, or notes');
      $table->string('phone', 20)->nullable()->comment('Patient phone number');
      $table->string('officer_name')->comment('Name of recording officer');
      $table->string('officer_role')->comment('Role of the recording officer, e.g., Data Officer');
      $table->string('officer_designation')->comment('Officer designation/title');
      $table->timestamps();

      // Add indexes for better performance
      $table->index(['user_id', 'date_of_visit']);
      $table->index(['facility_id', 'date_of_visit']);
      $table->index('section');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('clinical_notes');
  }
};
