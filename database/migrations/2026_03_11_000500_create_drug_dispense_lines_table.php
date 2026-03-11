<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('drug_dispense_lines', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();

      $table->foreignId('drug_catalog_item_id')->nullable()->constrained('drug_catalog_items')->nullOnDelete();
      $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('dispensed_date');
      $table->string('dispense_code', 32);
      $table->string('drug_name', 150);
      $table->decimal('quantity', 10, 2)->default(0);
      $table->text('dispense_notes')->nullable();
      $table->string('dispensed_by')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'dispensed_date']);
      $table->index(['patient_id', 'dispensed_date']);
      $table->index(['facility_id', 'dispense_code']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('drug_dispense_lines');
  }
};

