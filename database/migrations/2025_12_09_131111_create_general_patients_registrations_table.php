<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('general_patients_registrations', function (Blueprint $table) {
      $table->id();

      $table->foreignId('patient_id')->unique()->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

      $table->enum('marital_status', ['Single', 'Married', 'Widowed', 'Divorced'])->nullable();
      $table->string('occupation')->nullable();
      $table->string('religion')->nullable();
      $table->string('place_of_origin')->nullable();
      $table->string('tribe')->nullable();

      $table->text('home_address')->nullable();
      $table->string('town')->nullable();
      $table->string('landmark')->nullable();
      $table->string('po_box_no', 50)->nullable();

      $table->string('nok_name')->nullable();
      $table->string('nok_relationship')->nullable();
      $table->string('nok_phone', 20)->nullable();
      $table->text('nok_address')->nullable();

      $table->string('xray_no', 50)->nullable();

      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->foreignId('registered_by')->nullable()->constrained('users')->onDelete('set null');
      $table->timestamp('registration_date')->useCurrent();

      $table->timestamps();
      $table->softDeletes();

      $table->index('facility_id');
      $table->index('registration_date');
      $table->index('registered_by');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('general_patients_registrations');
  }
};
