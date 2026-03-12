<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('invoice_lines', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('invoice_id');
      $table->unsignedBigInteger('patient_id');
      $table->unsignedBigInteger('facility_id');
      $table->string('module', 80)->nullable();
      $table->string('reference_type', 120)->nullable();
      $table->unsignedBigInteger('reference_id')->nullable();
      $table->string('reference_code', 80)->nullable();
      $table->string('description', 255);
      $table->decimal('quantity', 12, 2)->default(1);
      $table->decimal('unit_price', 12, 2)->default(0);
      $table->decimal('line_amount', 12, 2)->default(0);
      $table->date('service_date')->nullable();
      $table->string('created_by', 191)->nullable();
      $table->softDeletes();
      $table->timestamps();

      $table->index(['invoice_id', 'created_at'], 'idx_invoice_lines_invoice_created');
      $table->index(['patient_id', 'facility_id'], 'idx_invoice_lines_patient_facility');
      $table->index(['module', 'reference_code'], 'idx_invoice_lines_module_refcode');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('invoice_lines');
  }
};

