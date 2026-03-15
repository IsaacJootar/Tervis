<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('referrals', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained('lgas')->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained('wards')->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('referral_date');
      $table->string('serial_no')->nullable();

      $table->string('referred_from')->nullable();
      $table->string('referred_to')->nullable();
      $table->string('requested_service_code')->nullable();

      $table->json('services_selected')->nullable();
      $table->text('services_other')->nullable();

      $table->enum('service_provided', ['Yes', 'No'])->nullable();
      $table->date('date_completed')->nullable();
      $table->enum('follow_up_needed', ['Yes', 'No'])->nullable();

      $table->enum('transport_mode', ['ambulance', 'ets', 'others'])->nullable();
      $table->time('time_in')->nullable();
      $table->time('time_out')->nullable();

      $table->string('completed_by')->nullable();
      $table->string('completed_designation')->nullable();
      $table->date('completed_date')->nullable();
      $table->string('focal_person')->nullable();
      $table->date('focal_date')->nullable();

      $table->json('summary_map')->nullable();
      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'month_year']);
      $table->index(['facility_id', 'referral_date']);
      $table->index(['patient_id', 'facility_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('referrals');
  }
};
