<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('payment_allocations', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('patient_payment_id');
      $table->unsignedBigInteger('invoice_id');
      $table->decimal('amount_allocated', 12, 2)->default(0);
      $table->timestamps();

      $table->index(['patient_payment_id'], 'idx_payment_allocations_payment');
      $table->index(['invoice_id'], 'idx_payment_allocations_invoice');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('payment_allocations');
  }
};

