<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('drug_stock_batches', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('drug_catalog_item_id')->constrained('drug_catalog_items')->onDelete('cascade');
      $table->string('batch_number', 120)->nullable();
      $table->date('received_date');
      $table->date('expiry_date')->nullable();
      $table->decimal('quantity_received', 12, 2)->default(0);
      $table->decimal('quantity_available', 12, 2)->default(0);
      $table->decimal('unit_cost', 12, 2)->nullable();
      $table->string('supplier_name', 160)->nullable();
      $table->text('notes')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'drug_catalog_item_id']);
      $table->index(['facility_id', 'received_date']);
      $table->index(['facility_id', 'expiry_date']);
      $table->index(['facility_id', 'is_active']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('drug_stock_batches');
  }
};

