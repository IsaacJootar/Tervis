<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('invoices', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('patient_id');
      $table->unsignedBigInteger('facility_id');
      $table->unsignedBigInteger('state_id')->nullable();
      $table->unsignedBigInteger('lga_id')->nullable();
      $table->unsignedBigInteger('ward_id')->nullable();
      $table->date('month_year')->nullable();
      $table->string('invoice_code', 40)->unique();
      $table->date('invoice_date');
      $table->decimal('total_amount', 12, 2)->default(0);
      $table->decimal('amount_paid', 12, 2)->default(0);
      $table->decimal('outstanding_amount', 12, 2)->default(0);
      $table->string('status', 40)->default('draft');
      $table->text('notes')->nullable();
      $table->string('created_by', 191)->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->index(['patient_id', 'facility_id', 'invoice_date'], 'idx_invoice_patient_facility_date');
      $table->index(['facility_id', 'status'], 'idx_invoice_facility_status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('invoices');
  }
};

