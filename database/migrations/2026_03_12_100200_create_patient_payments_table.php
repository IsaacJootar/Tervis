<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('patient_payments', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('patient_id');
      $table->unsignedBigInteger('facility_id');
      $table->unsignedBigInteger('state_id')->nullable();
      $table->unsignedBigInteger('lga_id')->nullable();
      $table->unsignedBigInteger('ward_id')->nullable();
      $table->date('month_year')->nullable();
      $table->string('payment_code', 40)->unique();
      $table->date('payment_date');
      $table->decimal('amount_received', 12, 2)->default(0);
      $table->string('payment_method', 60)->nullable();
      $table->text('notes')->nullable();
      $table->string('received_by', 191)->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->index(['patient_id', 'facility_id', 'payment_date'], 'idx_patient_payments_patient_facility_date');
      $table->index(['facility_id', 'payment_method'], 'idx_patient_payments_facility_method');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('patient_payments');
  }
};

