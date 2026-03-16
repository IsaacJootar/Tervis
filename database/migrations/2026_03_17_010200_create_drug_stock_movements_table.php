<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('drug_stock_movements', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('drug_catalog_item_id')->constrained('drug_catalog_items')->onDelete('cascade');
      $table->foreignId('drug_stock_batch_id')->nullable()->constrained('drug_stock_batches')->nullOnDelete();
      $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();

      $table->string('movement_type', 40);
      $table->decimal('quantity', 12, 2)->default(0);
      $table->decimal('balance_after', 12, 2)->default(0);
      $table->dateTime('moved_at');
      $table->string('moved_by', 160)->nullable();
      $table->string('reference_type', 120)->nullable();
      $table->unsignedBigInteger('reference_id')->nullable();
      $table->string('reference_code', 80)->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'drug_catalog_item_id']);
      $table->index(['facility_id', 'movement_type']);
      $table->index(['facility_id', 'moved_at']);
      $table->index(['reference_type', 'reference_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('drug_stock_movements');
  }
};

